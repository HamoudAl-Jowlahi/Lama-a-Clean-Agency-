<?php

namespace App\Models;

use App\Enums\WorkerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Worker extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['national_id'];

    protected function casts(): array
    {
        return [
            'status' => WorkerStatus::class,
            'national_id' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingAssignments(): HasMany
    {
        return $this->hasMany(WorkerAssignment::class);
    }

    public function contractAssignments(): HasMany
    {
        return $this->hasMany(ContractAssignment::class);
    }

    public function currentContractAssignment(): HasOne
    {
        // إسناد مفتوح واحد على الأكثر للعاملة (يُفرض في AvailabilityService)
        return $this->hasOne(ContractAssignment::class)->whereNull('ended_on');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
