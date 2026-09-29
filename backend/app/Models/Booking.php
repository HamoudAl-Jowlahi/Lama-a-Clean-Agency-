<?php

namespace App\Models;

use App\Enums\ActorType;
use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasStatusLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * زيارة تنظيف لمرة واحدة. لا تُغيَّر status مباشرة — عبر BookingStateMachine (Phase 3).
 */
class Booking extends Model
{
    use HasDocumentNumber, HasFactory, HasStatusLogs;

    public const DOCUMENT_PREFIX = 'BK';

    public const DOCUMENT_NUMBER_COLUMN = 'booking_number';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'cancelled_by_type' => ActorType::class,
            'address_snapshot' => 'array',
            'scheduled_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkerAssignment::class);
    }

    /** الإسناد الفعّال (بانتظار الرد أو مقبول). */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(WorkerAssignment::class)
            ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted])
            ->latestOfMany();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function scopeForCustomer(Builder $query, Customer $customer): void
    {
        $query->where('customer_id', $customer->id);
    }

    /** الزيارات المسندة حالياً لعاملة (للـ API الخاص بها). */
    public function scopeAssignedTo(Builder $query, Worker $worker): void
    {
        $query->whereHas('assignments', fn ($q) => $q
            ->where('worker_id', $worker->id)
            ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted]));
    }
}
