<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use App\Models\Concerns\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** طلب العميل لاستبدال العاملة أو إنهاء العقد. */
class ContractChangeRequest extends Model
{
    use HasDocumentNumber;

    public const DOCUMENT_PREFIX = 'RQ';

    public const DOCUMENT_NUMBER_COLUMN = 'request_number';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return [
            'type' => ChangeRequestType::class,
            'status' => ChangeRequestStatus::class,
            'requested_date' => 'date',
            'handled_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'handled_by');
    }

    public function resultingAssignment(): BelongsTo
    {
        return $this->belongsTo(ContractAssignment::class, 'resulting_assignment_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContractChangeRequestAttachment::class, 'change_request_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview], true);
    }
}
