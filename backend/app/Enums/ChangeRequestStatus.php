<?php

namespace App\Enums;

enum ChangeRequestStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'change_request_status';

    case Open = 'open';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
