<?php

namespace App\Enums;

enum ComplaintMessageKind: string
{
    use EnumHelpers;

    public const LANG_KEY = 'complaint_message_kind';

    case Message = 'message';
    case StatusChange = 'status_change';
    case InternalNote = 'internal_note';
}
