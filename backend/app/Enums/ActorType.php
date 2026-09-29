<?php

namespace App\Enums;

enum ActorType: string
{
    use EnumHelpers;

    public const LANG_KEY = 'actor_type';

    case Customer = 'customer';
    case Worker = 'worker';
    case Admin = 'admin';
    case System = 'system';
}
