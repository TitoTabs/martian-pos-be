<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'category', 'unit', 'stock', 'min_stock', 'cost_per_unit', 'is_active'])]
class InventoryItem extends Model
{
    public const UNITS = ['pcs', 'grams', 'kg', 'ml', 'liters', 'pack', 'box'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
