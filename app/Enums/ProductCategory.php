<?php

namespace App\Enums;

enum ProductCategory: string
{
    case IcedCoffee = 'Iced Coffee';
    case NonCoffee = 'Non Coffee';
    case MatchaSeries = 'Matcha Series';
    case Refreshers = 'Refreshers';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
