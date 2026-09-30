<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChangeRequestStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'change_request_status';

    case Open = 'open';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
