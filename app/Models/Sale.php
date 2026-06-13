<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subtotal', 'total', 'payment_method', 'customer_name', 'order_type', 'notes', 'status'])]
class Sale extends Model
{
    public const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];

    public const CANCELLED = 'cancelled';

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Scope to sales that count toward revenue/reports — everything except
     * cancelled orders, whose inventory has been restored and whose totals
     * must not appear in any sales figure.
     *
     * @param  Builder<Sale>  $query
     */
    public function scopeNotCancelled(Builder $query): void
    {
        $query->where('status', '!=', self::CANCELLED);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
