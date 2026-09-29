<?php

namespace App\Models\Concerns;

use App\Models\Booking;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * للجداول المرتبطة بزيارة **أو** عقد (payments, ratings, complaints).
 * يفرض "واحد بالضبط" على مستوى التطبيق في كل محركات قواعد البيانات؛
 * وفي MySQL/MariaDB يوجد أيضاً CHECK constraint.
 */
trait BelongsToBookingOrContract
{
    public static function bootBelongsToBookingOrContract(): void
    {
        static::saving(function ($model) {
            if (is_null($model->booking_id) === is_null($model->contract_id)) {
                throw new LogicException(class_basename($model).' must belong to exactly one of booking or contract.');
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
