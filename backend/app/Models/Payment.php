<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToBookingOrContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * استحقاق وتحصيل نقدي (CR-1): يُنشأ due عند إتمام الزيارة أو نهاية فترة العقد،
 * وتسجّل الإدارة collected أو waived.
 */
class Payment extends Model
{
    use BelongsToBookingOrContract;

    protected $guarded = ['id'];

    protected $attributes = [
        'method' => 'cash',
        'status' => 'due',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'collected_at' => 'datetime',
        ];
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'collected_by');
    }

    public function scopeDue(Builder $query): void
    {
        $query->where('status', PaymentStatus::Due);
    }
}
