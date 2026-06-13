<?php

namespace Database\Seeders;

use App\Enums\ProductCategory;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seeds sellable menu products with recipes (inventory items used
     * per unit sold). Runs after InventoryItemSeeder.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Americano', 'category' => ProductCategory::IcedCoffee, 'price' => 90, 'recipe' => ['Coffee Beans' => 18, 'Cups (16oz)' => 1, 'Straws' => 1, 'Ice' => 0.2]],
            ['name' => 'Iced Coffee', 'category' => ProductCategory::IcedCoffee, 'price' => 100, 'recipe' => ['Coffee Beans' => 20, 'Milk' => 200, 'Sugar' => 10, 'Cups (16oz)' => 1, 'Straws' => 1, 'Ice' => 0.25]],
            ['name' => 'Cafe Latte', 'category' => ProductCategory::IcedCoffee, 'price' => 120, 'recipe' => ['Coffee Beans' => 18, 'Milk' => 250, 'Cups (16oz)' => 1, 'Straws' => 1]],
            ['name' => 'Cappuccino', 'category' => ProductCategory::IcedCoffee, 'price' => 120, 'recipe' => ['Coffee Beans' => 18, 'Milk' => 200, 'Cups (16oz)' => 1]],
            ['name' => 'Spanish Latte', 'category' => ProductCategory::IcedCoffee, 'price' => 130, 'recipe' => ['Coffee Beans' => 18, 'Milk' => 220, 'Sugar' => 15, 'Cups (16oz)' => 1, 'Straws' => 1]],
            ['name' => 'Caramel Macchiato', 'category' => ProductCategory::IcedCoffee, 'price' => 140, 'recipe' => ['Coffee Beans' => 18, 'Milk' => 220, 'Caramel Syrup' => 30, 'Cups (16oz)' => 1, 'Straws' => 1]],
            ['name' => 'Mocha', 'category' => ProductCategory::IcedCoffee, 'price' => 135, 'recipe' => ['Coffee Beans' => 18, 'Milk' => 200, 'Chocolate Syrup' => 30, 'Cups (16oz)' => 1, 'Straws' => 1]],
            ['name' => 'Matcha Latte', 'category' => ProductCategory::MatchaSeries, 'price' => 140, 'recipe' => ['Matcha Powder' => 8, 'Milk' => 250, 'Sugar' => 10, 'Cups (16oz)' => 1, 'Straws' => 1]],
            ['name' => 'Chocolate Frappe', 'category' => ProductCategory::NonCoffee, 'price' => 150, 'recipe' => ['Chocolate Syrup' => 40, 'Milk' => 220, 'Whipped Cream' => 30, 'Cups (16oz)' => 1, 'Straws' => 1, 'Ice' => 0.3]],
            ['name' => 'Iced Tea', 'category' => ProductCategory::Refreshers, 'price' => 70, 'recipe' => ['Sugar' => 15, 'Cups (16oz)' => 1, 'Straws' => 1, 'Ice' => 0.25]],
        ];

        $items = InventoryItem::pluck('id', 'name');

        foreach ($products as $definition) {
            $product = Product::create([
                'name' => $definition['name'],
                'category' => $definition['category']->value,
                'price' => $definition['price'],
                'is_active' => true,
            ]);

            $ingredients = collect($definition['recipe'])
                ->mapWithKeys(fn ($quantity, $itemName) => [$items[$itemName] => ['quantity' => $quantity]])
                ->all();

            $product->ingredients()->sync($ingredients);
        }
    }
}
