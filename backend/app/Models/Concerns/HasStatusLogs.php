<?php

namespace App\Models\Concerns;

use App\Models\StatusLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** السجل الزمني لتغيّر الحالة (يغذي شاشة تتبع الطلب/العقد). */
trait HasStatusLogs
{
    public function statusLogs(): MorphMany
    {
        return $this->morphMany(StatusLog::class, 'loggable')->orderBy('created_at')->orderBy('id');
    }
}
