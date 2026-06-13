<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Coffee Beans', 'category' => 'Ingredients', 'unit' => 'grams', 'stock' => 5000, 'min_stock' => 1000, 'cost_per_unit' => 0.80],
            ['name' => 'Milk', 'category' => 'Ingredients', 'unit' => 'ml', 'stock' => 20000, 'min_stock' => 5000, 'cost_per_unit' => 0.09],
            ['name' => 'Oat Milk', 'category' => 'Ingredients', 'unit' => 'ml', 'stock' => 6000, 'min_stock' => 2000, 'cost_per_unit' => 0.18],
            ['name' => 'Sugar', 'category' => 'Ingredients', 'unit' => 'grams', 'stock' => 8000, 'min_stock' => 2000, 'cost_per_unit' => 0.06],
            ['name' => 'Matcha Powder', 'category' => 'Ingredients', 'unit' => 'grams', 'stock' => 900, 'min_stock' => 300, 'cost_per_unit' => 2.50],
            ['name' => 'Chocolate Syrup', 'category' => 'Ingredients', 'unit' => 'ml', 'stock' => 2500, 'min_stock' => 800, 'cost_per_unit' => 0.30],
            ['name' => 'Caramel Syrup', 'category' => 'Ingredients', 'unit' => 'ml', 'stock' => 700, 'min_stock' => 800, 'cost_per_unit' => 0.30],
            ['name' => 'Coffee Jelly', 'category' => 'Ingredients', 'unit' => 'grams', 'stock' => 1500, 'min_stock' => 500, 'cost_per_unit' => 0.20],
            ['name' => 'Whipped Cream', 'category' => 'Ingredients', 'unit' => 'ml', 'stock' => 1200, 'min_stock' => 400, 'cost_per_unit' => 0.25],
            ['name' => 'Cups (16oz)', 'category' => 'Packaging', 'unit' => 'pcs', 'stock' => 800, 'min_stock' => 200, 'cost_per_unit' => 5.00],
            ['name' => 'Straws', 'category' => 'Packaging', 'unit' => 'pcs', 'stock' => 150, 'min_stock' => 200, 'cost_per_unit' => 0.50],
            ['name' => 'Tissue', 'category' => 'Supplies', 'unit' => 'pack', 'stock' => 40, 'min_stock' => 10, 'cost_per_unit' => 35.00],
            ['name' => 'Ice', 'category' => 'Ingredients', 'unit' => 'kg', 'stock' => 60, 'min_stock' => 20, 'cost_per_unit' => 15.00],
        ];

        foreach ($items as $item) {
            InventoryItem::create($item + ['is_active' => true]);
        }
    }
}
