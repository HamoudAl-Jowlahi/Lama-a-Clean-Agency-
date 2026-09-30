<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * CR-3: نوعان منفصلان من الموظفين.
 * cleaner: عضو في فريق زيارات (زيارات فقط) · housekeeper: خادمة (عقود فقط).
 */
enum WorkerType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'worker_type';

    case Cleaner = 'cleaner';
    case Housekeeper = 'housekeeper';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Cleaner => 'primary',
            self::Housekeeper => 'info',
        };
    }
}
