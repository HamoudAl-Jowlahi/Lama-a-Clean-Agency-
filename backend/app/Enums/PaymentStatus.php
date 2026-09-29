<?php

namespace App\Enums;

enum PaymentStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'payment_status';

    case Due = 'due';
    case Collected = 'collected';
    case Waived = 'waived';
}
