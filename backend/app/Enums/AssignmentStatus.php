<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'assignment_status';

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
}
