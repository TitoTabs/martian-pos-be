<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * Create a sale, snapshot prices, and deduct inventory atomically.
     *
     * Stock is not tracked on products; each sold product deducts its
     * recipe ingredients, and each add-on deducts its linked inventory
     * item. Deductions apply per unit sold and may drive stock below
     * the minimum level (flagged on the dashboard) rather than block
     * the sale. Every sale enters the barista queue as a pending order.
     *
     * @param array{payment_method: string, customer_name: string, order_type: string, notes?: string|null, items: array<int, array{product_id: int, quantity: int, addon_ids?: array<int, int>}>} $data
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'subtotal' => 0,
                'total' => 0,
                'payment_method' => $data['payment_method'],
                'customer_name' => $data['customer_name'],
                'order_type' => $data['order_type'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $line) {
                $product = Product::with('ingredients')->findOrFail($line['product_id']);

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "{$product->name} is no longer available.",
                    ]);
                }

                $addons = Addon::whereIn('id', $line['addon_ids'] ?? [])
                    ->where('is_active', true)
                    ->get();

                // Add-ons apply per unit: 2 lattes with an extra shot = 2 extra shots.
                $lineTotal = ((float) $product->price + (float) $addons->sum('price')) * $line['quantity'];

                $item = $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ]);

                foreach ($addons as $addon) {
                    $item->addons()->create([
                        'addon_id' => $addon->id,
                        'addon_name' => $addon->name,
                        'price' => $addon->price,
                    ]);
                }

                $this->deductInventory($product, $addons, $line['quantity']);

                $subtotal += $lineTotal;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $sale->load('items.addons');
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Addon> $addons
     */
    private function deductInventory(Product $product, $addons, int $quantity): void
    {
        foreach ($product->ingredients as $ingredient) {
            InventoryItem::whereKey($ingredient->id)
                ->decrement('stock', (float) $ingredient->pivot->quantity * $quantity);
        }

        foreach ($addons as $addon) {
            if ($addon->inventory_item_id && $addon->quantity_used) {
                InventoryItem::whereKey($addon->inventory_item_id)
                    ->decrement('stock', (float) $addon->quantity_used * $quantity);
            }
        }
    }

    /**
     * Cancel an order: restore the inventory it consumed and mark it
     * cancelled so it drops out of the queue and every sales figure.
     * The sale row is kept (not deleted) so cancellations stay auditable.
     */
    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $sale->load('items.addons');

            foreach ($sale->items as $item) {
                $product = Product::with('ingredients')->find($item->product_id);

                if ($product) {
                    foreach ($product->ingredients as $ingredient) {
                        InventoryItem::whereKey($ingredient->id)
                            ->increment('stock', (float) $ingredient->pivot->quantity * $item->quantity);
                    }
                }

                $addons = Addon::whereIn('id', $item->addons->pluck('addon_id'))->get();

                foreach ($addons as $addon) {
                    if ($addon->inventory_item_id && $addon->quantity_used) {
                        InventoryItem::whereKey($addon->inventory_item_id)
                            ->increment('stock', (float) $addon->quantity_used * $item->quantity);
                    }
                }
            }

            $sale->update(['status' => Sale::CANCELLED]);

            return $sale->load('items.addons');
        });
    }
}
