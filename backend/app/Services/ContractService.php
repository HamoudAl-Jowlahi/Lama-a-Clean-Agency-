<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\AssignmentEndReason;
use App\Enums\ContractStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\ContractAssignment;
use App\Models\ContractPlan;
use App\Models\Customer;
use App\Models\Worker;
use App\StateMachines\ContractStateMachine;
use App\Support\AuditLogger;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * دورة حياة عقد الاستئجار (CR-2). استبدال العاملة في ContractChangeService.
 */
class ContractService
{
    public function __construct(
        private ContractStateMachine $machine,
        private AvailabilityService $availability,
        private PricingService $pricing,
        private ContractBillingService $billing,
    ) {}

    // ============================================================ customer

    /**
     * @param  array{plan_id: int, address_id: int, start_date: string, months: int, terms_version: string, customer_notes?: ?string}  $data
     */
    public function create(Customer $customer, array $data): Contract
    {
        $plan = ContractPlan::active()->findOrFail($data['plan_id']);
        $address = $customer->addresses()->findOrFail($data['address_id']);
        $start = CarbonImmutable::parse($data['start_date']);
        $months = (int) $data['months'];

        $this->validateTerms($plan, $start, $months, $data['terms_version']);
        $quote = $this->pricing->quoteContract($plan, $start, $months);

        return DB::transaction(function () use ($customer, $plan, $address, $quote, $data) {
            $contract = Contract::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'address_id' => $address->id,
                'address_snapshot' => $address->toSnapshot(),
                'plan_snapshot' => $plan->toSnapshot(),
                'start_date' => $quote['start_date'],
                'end_date' => $quote['end_date'],
                'months' => $quote['months'],
                'monthly_price' => $quote['monthly_price'],
                'total_amount' => $quote['total'],
                'currency' => $quote['currency'],
                'status' => ContractStatus::Pending,
                'terms_version' => $data['terms_version'],
                'terms_accepted_at' => now(),
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $contract->statusLogs()->create([
                'to_status' => ContractStatus::Pending->value,
                'actor_type' => ActorType::Customer,
                'actor_id' => $customer->user_id,
            ]);

            return $contract;
        });
    }

    public function validateTerms(ContractPlan $plan, CarbonImmutable $start, int $months, string $termsVersion): void
    {
        if ($termsVersion !== Settings::get('contracts.terms_version')) {
            throw BusinessRuleException::make('TERMS_OUTDATED', status: 422);
        }

        $min = max($plan->min_months, (int) Settings::get('contracts.min_months'));
        $max = min($plan->max_months, (int) Settings::get('contracts.max_months'));
        if ($months < $min || $months > $max) {
            throw BusinessRuleException::make('CONTRACT_MONTHS_OUT_OF_RANGE', ['min' => $min, 'max' => $max], 422);
        }

        $earliest = $this->today()->addDays((int) Settings::get('contracts.min_start_lead_days'));
        if ($start->lt($earliest)) {
            throw BusinessRuleException::make('CONTRACT_START_TOO_SOON', ['date' => $earliest->toDateString()], 422);
        }
    }

    public function cancelByCustomer(Contract $contract, Customer $customer, ?string $reason = null): Contract
    {
        if (! $contract->status->isCancellableByCustomer()) {
            throw BusinessRuleException::make('CANCEL_NOT_ALLOWED');
        }

        return $this->cancel($contract, ActorType::Customer, $customer->user_id, $reason);
    }

    // =============================================================== admin

    public function confirm(Contract $contract, AdminUser $admin): Contract
    {
        $this->machine->transition($contract, ContractStatus::Confirmed, ActorType::Admin, $admin->id, attributes: ['confirmed_at' => now()]);
        AuditLogger::log($admin, 'contract.confirmed', $contract);

        return $contract;
    }

    public function reject(Contract $contract, AdminUser $admin, string $reason): Contract
    {
        $this->machine->transition($contract, ContractStatus::Rejected, ActorType::Admin, $admin->id, $reason, ['cancel_reason' => $reason]);
        AuditLogger::log($admin, 'contract.rejected', $contract, new: ['reason' => $reason]);

        return $contract;
    }

    public function assign(Contract $contract, Worker $worker, AdminUser $admin): ContractAssignment
    {
        return DB::transaction(function () use ($contract, $worker, $admin) {
            $worker = Worker::lockForUpdate()->findOrFail($worker->id);
            $this->availability->ensureWorkerCanTakeContract($worker, CarbonImmutable::parse($contract->start_date), CarbonImmutable::parse($contract->end_date));

            $this->machine->transition($contract, ContractStatus::Assigned, ActorType::Admin, $admin->id);
            $assignment = $contract->assignments()->create([
                'worker_id' => $worker->id,
                'assigned_by' => $admin->id,
                'started_on' => $contract->start_date,
            ]);

            AuditLogger::log($admin, 'contract.assigned', $contract, new: ['worker_id' => $worker->id]);

            // عقد يبدأ اليوم (أو تأخر إسناده) يُفعّل فوراً
            $this->activateIfDue($contract, ActorType::System);

            return $assignment;
        });
    }

    /** سحب العاملة قبل بدء العقد (يعود إلى confirmed). */
    public function withdrawAssignment(Contract $contract, AdminUser $admin, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $admin, $reason) {
            $this->machine->transition($contract, ContractStatus::Confirmed, ActorType::Admin, $admin->id, $reason);
            $contract->currentAssignment()->delete(); // لم يبدأ العمل — لا تاريخ يُحفظ
            AuditLogger::log($admin, 'contract.assignment_withdrawn', $contract, new: ['reason' => $reason]);

            return $contract;
        });
    }

    public function cancelByAdmin(Contract $contract, AdminUser $admin, string $reason): Contract
    {
        $this->cancel($contract, ActorType::Admin, $admin->id, $reason);
        AuditLogger::log($admin, 'contract.cancelled', $contract, new: ['reason' => $reason]);

        return $contract;
    }

    /**
     * إنهاء مبكر لعقد ساري (بطلب معتمد من العميل أو بقرار إداري).
     * آخر يوم عمل = $lastDay، ثم يُنشأ الاستحقاق الأخير.
     */
    public function terminate(Contract $contract, AdminUser $admin, CarbonImmutable $lastDay, string $reason): Contract
    {
        $today = $this->today();
        $end = CarbonImmutable::parse($contract->end_date);
        if ($lastDay->lt($today) || $lastDay->gt($end)) {
            throw BusinessRuleException::make('TERMINATION_DATE_INVALID', ['date' => $end->toDateString()], 422);
        }

        return DB::transaction(function () use ($contract, $admin, $lastDay, $reason) {
            $this->machine->transition($contract, ContractStatus::Terminated, ActorType::Admin, $admin->id, $reason, [
                'ended_at' => $lastDay,
                'termination_reason' => $reason,
                'terminated_by' => $admin->id,
            ]);
            $contract->currentAssignment()->update(['ended_on' => $lastDay->toDateString(), 'end_reason' => AssignmentEndReason::Terminated]);
            $this->billing->generateDuePayments($contract->refresh());

            AuditLogger::log($admin, 'contract.terminated', $contract, new: ['last_day' => $lastDay->toDateString(), 'reason' => $reason]);

            return $contract;
        });
    }

    // ============================================================== system

    /** assigned ← active عند وصول تاريخ البدء. */
    public function activateIfDue(Contract $contract, ActorType $actor = ActorType::System, ?int $actorId = null): bool
    {
        if ($contract->status !== ContractStatus::Assigned || CarbonImmutable::parse($contract->start_date)->gt($this->today())) {
            return false;
        }

        $this->machine->transition($contract, ContractStatus::Active, $actor, $actorId, attributes: ['activated_at' => now()]);

        return true;
    }

    /** active ← completed بعد آخر يوم في العقد. */
    public function completeIfDue(Contract $contract): bool
    {
        $end = CarbonImmutable::parse($contract->end_date);
        if ($contract->status !== ContractStatus::Active || ! $end->lt($this->today())) {
            return false;
        }

        DB::transaction(function () use ($contract, $end) {
            $this->machine->transition($contract, ContractStatus::Completed, ActorType::System, attributes: ['ended_at' => $end]);
            $contract->currentAssignment()->update(['ended_on' => $end->toDateString(), 'end_reason' => AssignmentEndReason::ContractCompleted]);
        });

        return true;
    }

    /**
     * المهمة اليومية: تفعيل العقود، إنهاء المنتهية، وإنشاء الاستحقاقات.
     *
     * @return array{activated: int, completed: int, payments: int}
     */
    public function runDaily(): array
    {
        $stats = ['activated' => 0, 'completed' => 0, 'payments' => 0];

        Contract::where('status', ContractStatus::Assigned)->lazyById()->each(function (Contract $c) use (&$stats) {
            $stats['activated'] += (int) $this->activateIfDue($c);
        });

        Contract::where('status', ContractStatus::Active)->lazyById()->each(function (Contract $c) use (&$stats) {
            $stats['completed'] += (int) $this->completeIfDue($c);
        });

        Contract::whereIn('status', [ContractStatus::Active, ContractStatus::Completed, ContractStatus::Terminated])
            ->lazyById()->each(function (Contract $c) use (&$stats) {
                $stats['payments'] += $this->billing->generateDuePayments($c);
            });

        return $stats;
    }

    // ============================================================= private

    private function cancel(Contract $contract, ActorType $actor, int $actorId, ?string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $actorId, $reason) {
            $this->machine->transition($contract, ContractStatus::Cancelled, $actor, $actorId, $reason, ['cancel_reason' => $reason]);
            $contract->currentAssignment()->delete(); // الإلغاء قبل البدء فقط

            return $contract;
        });
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->availability->now()->toDateString());
    }
}
