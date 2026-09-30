<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssignmentEndReason: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'assignment_end_reason';

    case Replaced = 'replaced';
    case ContractCompleted = 'contract_completed';
    case Terminated = 'terminated';
    case WorkerUnavailable = 'worker_unavailable';
}
