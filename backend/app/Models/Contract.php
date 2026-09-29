<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasStatusLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * عقد استئجار عاملة (CR-2). استبدال العاملة يغيّر contract_assignments فقط،
 * وتغيير الحالة عبر ContractStateMachine (Phase 3).
 */
class Contract extends Model
{
    use HasDocumentNumber, HasFactory, HasStatusLogs;

    public const DOCUMENT_PREFIX = 'CT';

    public const DOCUMENT_NUMBER_COLUMN = 'contract_number';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'address_snapshot' => 'array',
            'plan_snapshot' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'terms_accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'activated_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContractPlan::class, 'plan_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }

    /** كل العاملات اللواتي عملن في العقد، بالترتيب. */
    public function assignments(): HasMany
    {
        return $this->hasMany(ContractAssignment::class)->orderBy('started_on')->orderBy('id');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(ContractAssignment::class)->whereNull('ended_on');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ContractChangeRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('due_date');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'terminated_by');
    }

    public function scopeForCustomer(Builder $query, Customer $customer): void
    {
        $query->where('customer_id', $customer->id);
    }

    /** العقود التي تعمل فيها العاملة حالياً. */
    public function scopeCurrentlyAssignedTo(Builder $query, Worker $worker): void
    {
        $query->whereHas('assignments', fn ($q) => $q->where('worker_id', $worker->id)->whereNull('ended_on'));
    }
}
