<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\AssignmentEndReason;
use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use App\Enums\ContractStatus;
use App\Events\DomainEvent;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\ContractAssignment;
use App\Models\ContractChangeRequest;
use App\Models\Customer;
use App\Models\Worker;
use App\Support\AuditLogger;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * طلبات العميل على عقد ساري: استبدال العاملة ("إخراجها") أو إنهاء العقد.
 * العميل يطلب ← الإدارة تراجع وتعتمد أو ترفض. الاستبدال لا يوقف العقد.
 */
class ContractChangeService
{
    public function __construct(
        private ContractService $contracts,
        private AvailabilityService $availability,
    ) {}

    // ============================================================ customer

    /**
     * @param  array{type: string, reason_type: string, details?: ?string, requested_date?: ?string}  $data
     * @param  list<UploadedFile>  $files
     */
    public function submit(Contract $contract, Customer $customer, array $data, array $files = []): ContractChangeRequest
    {
        if ($contract->status !== ContractStatus::Active) {
            throw BusinessRuleException::make('CONTRACT_NOT_ACTIVE');
        }

        $type = ChangeRequestType::from($data['type']);

        $alreadyOpen = $contract->changeRequests()
            ->where('type', $type)
            ->whereIn('status', [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview])
            ->exists();
        if ($alreadyOpen) {
            throw BusinessRuleException::make('REQUEST_ALREADY_OPEN');
        }

        if ($type === ChangeRequestType::ReplaceWorker) {
            $max = (int) Settings::get('contracts.max_replacements');
            $used = $contract->changeRequests()->where('type', $type)->where('status', ChangeRequestStatus::Approved)->count();
            if ($max > 0 && $used >= $max) {
                throw BusinessRuleException::make('REPLACEMENT_LIMIT_REACHED', ['max' => $max]);
            }
        }

        $requestedDate = null;
        if ($type === ChangeRequestType::Terminate) {
            $requestedDate = CarbonImmutable::parse($data['requested_date']);
            if ($requestedDate->lt($this->today()) || $requestedDate->gt(CarbonImmutable::parse($contract->end_date))) {
                throw BusinessRuleException::make('TERMINATION_DATE_INVALID', ['date' => $contract->end_date->toDateString()], 422);
            }
        }

        return DB::transaction(function () use ($contract, $customer, $type, $data, $requestedDate, $files) {
            $request = $contract->changeRequests()->create([
                'customer_id' => $customer->id,
                'type' => $type,
                'reason_type' => $data['reason_type'],
                'details' => $data['details'] ?? null,
                'requested_date' => $requestedDate?->toDateString(),
                'status' => ChangeRequestStatus::Open,
            ]);

            foreach ($files as $file) {
                $request->attachments()->create([
                    'path' => $file->store("change-requests/{$request->id}", config('agency.attachments.disk')),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            DomainEvent::afterCommit('change_request.submitted', $request, ['actor' => ActorType::Customer]);

            return $request;
        });
    }

    // =============================================================== admin

    public function markUnderReview(ContractChangeRequest $request, AdminUser $admin): ContractChangeRequest
    {
        $this->ensureStatus($request, [ChangeRequestStatus::Open]);
        $request->update(['status' => ChangeRequestStatus::UnderReview, 'handled_by' => $admin->id]);
        AuditLogger::log($admin, 'change_request.under_review', $request);

        return $request;
    }

    public function reject(ContractChangeRequest $request, AdminUser $admin, string $response): ContractChangeRequest
    {
        $this->ensureStatus($request, [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview]);
        $request->update([
            'status' => ChangeRequestStatus::Rejected,
            'admin_response' => $response,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);
        AuditLogger::log($admin, 'change_request.rejected', $request, new: ['response' => $response]);
        DomainEvent::afterCommit('change_request.rejected', $request, ['actor' => ActorType::Admin]);

        return $request;
    }

    /**
     * اعتماد الاستبدال: تنتهي فترة العاملة الحالية اليوم، وتبدأ البديلة في $startOn.
     * الأيام بينهما بلا عاملة (تُخصم إن كان deduct_waiting_days مفعلاً).
     */
    public function approveReplacement(ContractChangeRequest $request, AdminUser $admin, Worker $newWorker, CarbonImmutable $startOn, ?string $response = null): ContractAssignment
    {
        $this->ensureStatus($request, [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview]);
        if ($request->type !== ChangeRequestType::ReplaceWorker) {
            throw BusinessRuleException::make('REQUEST_TYPE_MISMATCH', status: 422);
        }

        $contract = Contract::findOrFail($request->contract_id);
        if ($contract->status !== ContractStatus::Active) {
            throw BusinessRuleException::make('CONTRACT_NOT_ACTIVE');
        }

        $today = $this->today();
        $end = CarbonImmutable::parse($contract->end_date);
        if ($startOn->lt($today) || $startOn->gt($end)) {
            throw BusinessRuleException::make('REPLACEMENT_DATE_INVALID', ['date' => $end->toDateString()], 422);
        }

        return DB::transaction(function () use ($request, $admin, $newWorker, $startOn, $response, $contract, $today, $end) {
            $current = $contract->currentAssignment()->lockForUpdate()->first();
            if ($current && $current->worker_id === $newWorker->id) {
                throw BusinessRuleException::make('REPLACEMENT_SAME_WORKER', status: 422);
            }

            $newWorker = Worker::lockForUpdate()->findOrFail($newWorker->id);
            $this->availability->ensureWorkerCanTakeContract($newWorker, $startOn, $end);

            $current?->update(['ended_on' => $today->toDateString(), 'end_reason' => AssignmentEndReason::Replaced]);

            $assignment = $contract->assignments()->create([
                'worker_id' => $newWorker->id,
                'assigned_by' => $admin->id,
                'started_on' => $startOn->toDateString(),
            ]);

            $request->update([
                'status' => ChangeRequestStatus::Approved,
                'admin_response' => $response,
                'handled_by' => $admin->id,
                'handled_at' => now(),
                'resulting_assignment_id' => $assignment->id,
            ]);

            AuditLogger::log($admin, 'change_request.replacement_approved', $request, [
                'worker_id' => $current?->worker_id,
            ], [
                'worker_id' => $newWorker->id,
                'starts_on' => $startOn->toDateString(),
            ]);

            // العميل والخادمة الجديدة يعلمان بالتعيين، والسابقة بانتهاء فترتها
            $context = ['actor' => ActorType::Admin, 'start_on' => $startOn->toDateString()];
            DomainEvent::afterCommit('contract.worker_changed', $contract, $context + ['worker' => $newWorker]);
            if ($current) {
                DomainEvent::afterCommit('contract.worker_released', $contract, $context + ['worker' => Worker::find($current->worker_id)]);
            }

            return $assignment;
        });
    }

    public function approveTermination(ContractChangeRequest $request, AdminUser $admin, ?string $response = null): Contract
    {
        $this->ensureStatus($request, [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview]);
        if ($request->type !== ChangeRequestType::Terminate) {
            throw BusinessRuleException::make('REQUEST_TYPE_MISMATCH', status: 422);
        }

        return DB::transaction(function () use ($request, $admin, $response) {
            $contract = $this->contracts->terminate(
                Contract::findOrFail($request->contract_id),
                $admin,
                CarbonImmutable::parse($request->requested_date)->max($this->today()),
                $request->reason_type,
            );

            $request->update([
                'status' => ChangeRequestStatus::Approved,
                'admin_response' => $response,
                'handled_by' => $admin->id,
                'handled_at' => now(),
            ]);
            AuditLogger::log($admin, 'change_request.termination_approved', $request);

            return $contract;
        });
    }

    // ============================================================= private

    /** @param list<ChangeRequestStatus> $allowed */
    private function ensureStatus(ContractChangeRequest $request, array $allowed): void
    {
        if (! in_array($request->status, $allowed, true)) {
            throw BusinessRuleException::make('REQUEST_ALREADY_HANDLED');
        }
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->availability->now()->toDateString());
    }
}
