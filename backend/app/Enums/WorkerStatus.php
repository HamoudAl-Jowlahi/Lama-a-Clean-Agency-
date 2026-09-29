<?php

namespace App\Enums;

enum WorkerStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'worker_status';

    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';
}
