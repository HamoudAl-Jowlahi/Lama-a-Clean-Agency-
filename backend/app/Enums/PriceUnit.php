<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PriceUnit: string implements HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'price_unit';

    case Fixed = 'fixed';
    case Hour = 'hour';
    case Piece = 'piece';
    case SquareMeter = 'square_meter';
}
