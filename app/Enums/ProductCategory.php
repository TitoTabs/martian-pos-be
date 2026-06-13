<?php

namespace App\Enums;

enum ProductCategory: string
{
    case IcedCoffee = 'Iced Coffee';
    case NonCoffee = 'Non Coffee';
    case MatchaSeries = 'Matcha Series';
    case Refreshers = 'Refreshers';
    case Pastries = 'Pastries';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Categories sold in the POS but excluded from business performance
     * figures (sales totals, reports, savings, capital recovery, profit
     * distribution). Pastries sell to customers but never count toward
     * these calculations.
     *
     * @return list<string>
     */
    public static function nonRevenue(): array
    {
        return [self::Pastries->value];
    }
}
