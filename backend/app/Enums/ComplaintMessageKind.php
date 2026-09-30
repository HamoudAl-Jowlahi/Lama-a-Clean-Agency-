<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ComplaintMessageKind: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'complaint_message_kind';

    case Message = 'message';
    case StatusChange = 'status_change';
    case InternalNote = 'internal_note';
}
