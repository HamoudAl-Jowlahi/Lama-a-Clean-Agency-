<?php

namespace App\Enums;

enum UserStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'user_status';

    case Active = 'active';
    case Suspended = 'suspended';
}
