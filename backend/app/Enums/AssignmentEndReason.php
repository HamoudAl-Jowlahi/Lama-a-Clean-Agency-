<?php

namespace App\Enums;

enum AssignmentEndReason: string
{
    use EnumHelpers;

    public const LANG_KEY = 'assignment_end_reason';

    case Replaced = 'replaced';
    case ContractCompleted = 'contract_completed';
    case Terminated = 'terminated';
    case WorkerUnavailable = 'worker_unavailable';
}
