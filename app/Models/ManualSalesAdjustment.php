<?php

namespace App\Models;

use Database\Factories\ManualSalesAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Financial-only sales record for dates without itemized POS orders.
 * Never linked to products, inventory, or the barista queue.
 */
#[Fillable(['date', 'amount', 'notes'])]
class ManualSalesAdjustment extends Model
{
    /** @use HasFactory<ManualSalesAdjustmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }
}
