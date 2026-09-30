<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssignmentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'assignment_status';

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Withdrawn => 'gray',
        };
    }
}
