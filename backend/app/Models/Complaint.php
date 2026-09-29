<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Models\Concerns\BelongsToBookingOrContract;
use App\Models\Concerns\HasDocumentNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use BelongsToBookingOrContract, HasDocumentNumber;

    public const DOCUMENT_PREFIX = 'CM';

    public const DOCUMENT_NUMBER_COLUMN = 'complaint_number';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_admin_id');
    }

    /** السجل الزمني كاملاً (بما فيه الملاحظات الداخلية — للإدارة فقط). */
    public function messages(): HasMany
    {
        return $this->hasMany(ComplaintMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function scopeForCustomer(Builder $query, Customer $customer): void
    {
        $query->where('customer_id', $customer->id);
    }
}
