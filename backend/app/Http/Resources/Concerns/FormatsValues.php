<?php

namespace App\Http\Resources\Concerns;

use BackedEnum;
use DateTimeInterface;

/** تنسيق موحد لكل الردود: الحالات {value, label}، المبالغ نصاً بخانتين، التواريخ ISO. */
trait FormatsValues
{
    protected function enum(?BackedEnum $value): ?array
    {
        return $value ? ['value' => $value->value, 'label' => $value->label()] : null;
    }

    protected function money(mixed $amount): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 2, '.', '');
    }

    protected function date(?DateTimeInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    protected function time(mixed $time): ?string
    {
        return $time === null ? null : substr((string) $time, 0, 5);
    }

    protected function datetime(?DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    /** name_ar / name_en حسب لغة الطلب، مع الرجوع للعربية. */
    protected function localized(string $field): ?string
    {
        $en = $this->resource->{$field.'_en'} ?? null;

        return app()->getLocale() === 'en' && $en ? $en : $this->resource->{$field.'_ar'};
    }

    /**
     * السجل الزمني للحالة (شاشة التتبع).
     *
     * @param  iterable<\App\Models\StatusLog>  $logs
     * @param  class-string<BackedEnum>  $enumClass
     */
    protected function timeline(iterable $logs, string $enumClass): array
    {
        return collect($logs)->map(fn ($log) => [
            'status' => $this->enum($enumClass::from($log->to_status)),
            'actor' => $this->enum($log->actor_type),
            'note' => $log->note,
            'at' => $this->datetime($log->created_at),
        ])->values()->all();
    }

    /** الاسم الأول فقط — لا نكشف الاسم الكامل للطرف الآخر. */
    protected function firstName(?string $name): ?string
    {
        return $name === null ? null : strtok($name, ' ');
    }
}
