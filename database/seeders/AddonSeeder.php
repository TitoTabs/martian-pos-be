<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    /**
     * Seeds sellable add-ons, optionally linked to the inventory item
     * they consume, and makes them available on all drink products.
     * Runs after InventoryItemSeeder and ProductSeeder.
     */
    public function run(): void
    {
        $items = InventoryItem::pluck('id', 'name');

        $addons = [
            ['name' => 'Coffee Jelly', 'price' => 20, 'item' => 'Coffee Jelly', 'quantity_used' => 50],
            ['name' => 'Oat Milk Substitute', 'price' => 30, 'item' => 'Oat Milk', 'quantity_used' => 200],
            ['name' => 'Extra Shot', 'price' => 25, 'item' => 'Coffee Beans', 'quantity_used' => 10],
            ['name' => 'Whipped Cream', 'price' => 15, 'item' => 'Whipped Cream', 'quantity_used' => 30],
        ];

        $productIds = Product::pluck('id');

        foreach ($addons as $definition) {
            $addon = Addon::create([
                'name' => $definition['name'],
                'price' => $definition['price'],
                'inventory_item_id' => $items[$definition['item']] ?? null,
                'quantity_used' => $definition['quantity_used'],
                'is_active' => true,
            ]);

            $addon->products()->sync($productIds);
        }
    }
}
