<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'user_status';

    case Active = 'active';
    case Suspended = 'suspended';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }
}
