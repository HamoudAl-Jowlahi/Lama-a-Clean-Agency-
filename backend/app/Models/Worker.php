<?php

namespace App\Models;

use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * موظف ميداني. النوع يحدد ما يعمل فيه (CR-3):
 * cleaner → عضو في فريق زيارات · housekeeper → خادمة بعقود.
 */
class Worker extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['national_id'];

    protected function casts(): array
    {
        return [
            'type' => WorkerType::class,
            'status' => WorkerStatus::class,
            'national_id' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** فريق واحد على الأكثر (UNIQUE على team_members.worker_id). */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')->withTimestamps();
    }

    public function team(): ?Team
    {
        return $this->teams()->first();
    }

    public function contractAssignments(): HasMany
    {
        return $this->hasMany(ContractAssignment::class);
    }

    public function currentContractAssignment(): HasOne
    {
        // إسناد مفتوح واحد على الأكثر للخادمة (يُفرض في AvailabilityService)
        return $this->hasOne(ContractAssignment::class)->whereNull('ended_on');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function isCleaner(): bool
    {
        return $this->type === WorkerType::Cleaner;
    }

    public function isHousekeeper(): bool
    {
        return $this->type === WorkerType::Housekeeper;
    }
}
