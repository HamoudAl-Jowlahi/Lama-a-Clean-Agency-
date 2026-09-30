<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ComplaintStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'complaint_status';

    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::UnderReview], true);
    }

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::UnderReview => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }
}
