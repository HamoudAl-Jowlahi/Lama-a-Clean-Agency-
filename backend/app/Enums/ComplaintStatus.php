<?php

namespace App\Enums;

enum ComplaintStatus: string
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
}
