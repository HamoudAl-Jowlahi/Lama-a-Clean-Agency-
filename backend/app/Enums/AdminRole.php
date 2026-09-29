<?php

namespace App\Enums;

enum AdminRole: string
{
    use EnumHelpers;

    public const LANG_KEY = 'admin_role';

    case SuperAdmin = 'super_admin';
    case Operations = 'operations';
    case Support = 'support';
}
