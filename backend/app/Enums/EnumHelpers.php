<?php

namespace App\Enums;

/**
 * مشترك لكل الـ Enums: القيم النصية هي نفسها في الـ API وتطبيق Flutter،
 * والنصوص المعروضة تأتي من lang/{locale}/enums.php.
 */
trait EnumHelpers
{
    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('enums.'.self::LANG_KEY.'.'.$this->value);
    }

    /** Filament\Support\Contracts\HasLabel — نفس النص في لوحة الإدارة. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** @return array<string, string> value => label (للقوائم في لوحة الإدارة) */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
