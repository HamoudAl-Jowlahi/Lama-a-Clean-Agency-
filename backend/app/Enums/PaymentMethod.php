<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'payment_method';

    case Cash = 'cash';
}
