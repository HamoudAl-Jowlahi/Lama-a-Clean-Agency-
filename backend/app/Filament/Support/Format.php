<?php

namespace App\Filament\Support;

use BackedEnum;

/** تنسيق موحد للعرض في لوحة الإدارة. */
final class Format
{
    public static function money(mixed $amount, ?string $currency = null): string
    {
        if ($amount === null) {
            return '—';
        }

        return number_format((float) $amount, 2).($currency ? ' '.$currency : '');
    }

    /** العنوان المحفوظ داخل الطلب/العقد في سطر واحد. */
    public static function address(?array $snapshot): string
    {
        if (! $snapshot) {
            return '—';
        }

        return collect([
            $snapshot['label'] ?? null,
            $snapshot['district'] ?? null,
            $snapshot['street'] ?? null,
            isset($snapshot['building']) ? 'مبنى '.$snapshot['building'] : null,
            isset($snapshot['floor']) ? 'الدور '.$snapshot['floor'] : null,
            $snapshot['city'] ?? null,
        ])->filter()->implode('، ');
    }

    /** نص حالة مخزنة كقيمة نصية (status_logs) عبر الـ Enum المناسب. */
    public static function enumLabel(string $enumClass, ?string $value): string
    {
        /** @var class-string<BackedEnum> $enumClass */
        return $value ? ($enumClass::tryFrom($value)?->label() ?? $value) : '—';
    }

    public static function enumColor(string $enumClass, ?string $value): ?string
    {
        return $value ? $enumClass::tryFrom($value)?->getColor() : null;
    }
}
