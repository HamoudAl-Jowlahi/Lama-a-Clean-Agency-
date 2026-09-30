<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'user_role';

    case Customer = 'customer';
    case Worker = 'worker';
}
