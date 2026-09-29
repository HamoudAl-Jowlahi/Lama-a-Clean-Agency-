<?php

namespace App\Enums;

enum PriceUnit: string
{
    use EnumHelpers;

    public const LANG_KEY = 'price_unit';

    case Fixed = 'fixed';
    case Hour = 'hour';
    case Piece = 'piece';
    case SquareMeter = 'square_meter';
}
