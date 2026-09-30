<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActorType: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'actor_type';

    case Customer = 'customer';
    case Worker = 'worker';
    case Admin = 'admin';
    case System = 'system';
}
