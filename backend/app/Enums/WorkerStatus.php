<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WorkerStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'worker_status';

    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
            self::OnLeave => 'warning',
        };
    }
}
