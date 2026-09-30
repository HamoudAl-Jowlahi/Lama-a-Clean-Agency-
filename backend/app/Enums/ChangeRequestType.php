<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChangeRequestType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'change_request_type';

    case ReplaceWorker = 'replace_worker';
    case Terminate = 'terminate';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::ReplaceWorker => 'info',
            self::Terminate => 'danger',
        };
    }
}
