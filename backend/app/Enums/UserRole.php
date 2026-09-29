<?php

namespace App\Enums;

enum UserRole: string
{
    use EnumHelpers;

    public const LANG_KEY = 'user_role';

    case Customer = 'customer';
    case Worker = 'worker';
}
