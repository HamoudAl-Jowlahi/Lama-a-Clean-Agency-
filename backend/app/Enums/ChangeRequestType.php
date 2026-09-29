<?php

namespace App\Enums;

enum ChangeRequestType: string
{
    use EnumHelpers;

    public const LANG_KEY = 'change_request_type';

    case ReplaceWorker = 'replace_worker';
    case Terminate = 'terminate';
}
