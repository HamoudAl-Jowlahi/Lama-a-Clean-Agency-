<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'payment_status';

    case Due = 'due';
    case Collected = 'collected';
    case Waived = 'waived';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Due => 'warning',
            self::Collected => 'success',
            self::Waived => 'gray',
        };
    }
}
