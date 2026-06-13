<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Seed demo sales spread over the past month so dashboard
     * period filters have data to show.
     */
    public function run(): void
    {
        $products = Product::where('is_active', true)->get();
        $addons = Addon::where('is_active', true)->get();

        if ($products->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 30; $i++) {
            // First few orders are recent and still in the barista queue;
            // the rest are older, completed history.
            $isActive = $i < 4;
            $createdAt = $isActive
                ? now()->subMinutes(rand(5, 60))
                : now()->subDays(rand(0, 29))->setTime(rand(8, 20), rand(0, 59));

            $sale = Sale::create([
                'subtotal' => 0,
                'total' => 0,
                'payment_method' => fake()->randomElement(['cash', 'gcash', 'card']),
                'customer_name' => fake()->firstName(),
                'order_type' => fake()->randomElement(['dine_in', 'take_out']),
                'notes' => rand(0, 3) === 0 ? fake()->randomElement(['Less ice', 'Extra hot', 'No sugar', 'Separate bags']) : null,
                'status' => $isActive ? 'pending' : 'completed',
            ]);

            $subtotal = 0;

            foreach ($products->random(rand(1, 3)) as $product) {
                $quantity = rand(1, 3);
                $lineAddons = $addons->isNotEmpty() && rand(0, 1)
                    ? $addons->random(rand(1, 2))
                    : collect();

                $lineTotal = ((float) $product->price + (float) $lineAddons->sum('price')) * $quantity;

                $item = $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
                $item->timestamps = false;
                $item->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);

                foreach ($lineAddons as $addon) {
                    $item->addons()->create([
                        'addon_id' => $addon->id,
                        'addon_name' => $addon->name,
                        'price' => $addon->price,
                    ]);
                }

                $subtotal += $lineTotal;
            }

            $sale->timestamps = false;
            $sale->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
