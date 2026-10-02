<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\ComplaintMessageKind;
use App\Enums\ComplaintStatus;
use App\Events\DomainEvent;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\Customer;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * الشكاوى وسجلها الزمني. كل شكوى مرتبطة بزيارة أو عقد يملكه العميل.
 * open ─► under_review ─► resolved ─► closed (والإدارة تستطيع الإغلاق مباشرة).
 */
class ComplaintService
{
    private const TRANSITIONS = [
        'open' => ['under_review', 'resolved', 'closed'],
        'under_review' => ['resolved', 'closed'],
        'resolved' => ['closed'],
        'closed' => [],
    ];

    /**
     * @param  array{booking_id?: ?int, contract_id?: ?int, type: string, description: string}  $data
     * @param  list<UploadedFile>  $files
     */
    public function create(Customer $customer, array $data, array $files = []): Complaint
    {
        // الملكية: الطلب/العقد يجب أن يخص نفس العميل (وإلا 404)
        $bookingId = isset($data['booking_id']) ? $customer->bookings()->findOrFail($data['booking_id'])->id : null;
        $contractId = isset($data['contract_id']) ? $customer->contracts()->findOrFail($data['contract_id'])->id : null;

        if (($bookingId === null) === ($contractId === null)) {
            throw BusinessRuleException::make('COMPLAINT_SUBJECT_REQUIRED', status: 422);
        }

        return DB::transaction(function () use ($customer, $data, $files, $bookingId, $contractId) {
            $complaint = Complaint::create([
                'customer_id' => $customer->id,
                'booking_id' => $bookingId,
                'contract_id' => $contractId,
                'type' => $data['type'],
                'description' => $data['description'],
                'status' => ComplaintStatus::Open,
            ]);

            $message = $complaint->messages()->create([
                'sender_type' => ActorType::Customer,
                'sender_id' => $customer->user_id,
                'kind' => ComplaintMessageKind::Message,
                'body' => $data['description'],
            ]);
            $this->storeFiles($complaint, $message, $files);
            DomainEvent::afterCommit('complaint.created', $complaint, ['actor' => ActorType::Customer]);

            return $complaint;
        });
    }

    /** @param list<UploadedFile> $files */
    public function addCustomerMessage(Complaint $complaint, Customer $customer, string $body, array $files = []): ComplaintMessage
    {
        if ($complaint->status === ComplaintStatus::Closed) {
            throw BusinessRuleException::make('COMPLAINT_CLOSED');
        }

        return DB::transaction(function () use ($complaint, $customer, $body, $files) {
            $message = $complaint->messages()->create([
                'sender_type' => ActorType::Customer,
                'sender_id' => $customer->user_id,
                'kind' => ComplaintMessageKind::Message,
                'body' => $body,
            ]);
            $this->storeFiles($complaint, $message, $files);
            DomainEvent::afterCommit('complaint.customer_message', $complaint, ['actor' => ActorType::Customer]);

            return $message;
        });
    }

    // =============================================================== admin

    public function reply(Complaint $complaint, AdminUser $admin, string $body, bool $internal = false): ComplaintMessage
    {
        if ($complaint->status === ComplaintStatus::Closed && ! $internal) {
            throw BusinessRuleException::make('COMPLAINT_CLOSED');
        }

        $message = $complaint->messages()->create([
            'sender_type' => ActorType::Admin,
            'sender_id' => $admin->id,
            'kind' => $internal ? ComplaintMessageKind::InternalNote : ComplaintMessageKind::Message,
            'body' => $body,
        ]);

        if (! $internal) { // الملاحظة الداخلية لا تُشعر العميل
            DomainEvent::afterCommit('complaint.replied', $complaint, ['actor' => ActorType::Admin]);
        }

        return $message;
    }

    public function changeStatus(Complaint $complaint, AdminUser $admin, ComplaintStatus $to): Complaint
    {
        $from = $complaint->status;
        if (! in_array($to->value, self::TRANSITIONS[$from->value], true)) {
            throw BusinessRuleException::make('INVALID_TRANSITION', ['from' => $from->label(), 'to' => $to->label()]);
        }

        return DB::transaction(function () use ($complaint, $admin, $from, $to) {
            $complaint->update([
                'status' => $to,
                'assigned_admin_id' => $complaint->assigned_admin_id ?? $admin->id,
                'resolved_at' => $to === ComplaintStatus::Resolved ? now() : $complaint->resolved_at,
                'closed_at' => $to === ComplaintStatus::Closed ? now() : null,
            ]);
            $complaint->messages()->create([
                'sender_type' => ActorType::Admin,
                'sender_id' => $admin->id,
                'kind' => ComplaintMessageKind::StatusChange,
                'meta' => ['from' => $from->value, 'to' => $to->value],
            ]);
            AuditLogger::log($admin, 'complaint.status_changed', $complaint, ['status' => $from->value], ['status' => $to->value]);
            DomainEvent::afterCommit('complaint.status_changed', $complaint, ['actor' => ActorType::Admin]);

            return $complaint;
        });
    }

    /** @param list<UploadedFile> $files */
    private function storeFiles(Complaint $complaint, ComplaintMessage $message, array $files): void
    {
        foreach ($files as $file) {
            $complaint->attachments()->create([
                'complaint_message_id' => $message->id,
                'path' => $file->store("complaints/{$complaint->id}", config('agency.attachments.disk')),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
