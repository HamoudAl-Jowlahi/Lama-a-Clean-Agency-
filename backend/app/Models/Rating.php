<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBookingOrContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use BelongsToBookingOrContract;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** للعقود: الخادمة المُقيَّمة. */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /** للزيارات: الفريق المُقيَّم (CR-3). */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
