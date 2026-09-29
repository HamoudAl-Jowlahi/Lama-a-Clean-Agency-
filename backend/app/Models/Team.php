<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * فريق زيارات ثابت (CR-3): قائد + أعضاء من نوع cleaner.
 * القائد وحده يقبل الزيارة ويحدّث حالتها ويستلم المبلغ نقداً.
 * الإنشاء والتعديل عبر TeamService (يتحقق من الأنواع والقائد).
 */
class Team extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'team_members')->withTimestamps();
    }

    public function bookingAssignments(): HasMany
    {
        return $this->hasMany(BookingAssignment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function isLeader(Worker $worker): bool
    {
        return $this->leader_id === $worker->id;
    }

    /** فريق يمكن إسناد زيارات له: مفعّل وله قائد. */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)->whereNotNull('leader_id');
    }
}
