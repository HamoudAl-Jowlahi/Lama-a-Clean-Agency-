<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use EnumHelpers;

    public const LANG_KEY = 'payment_method';

    case Cash = 'cash';
}
