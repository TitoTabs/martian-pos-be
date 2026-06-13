<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InventoryItemSeeder::class,
            ProductSeeder::class,
            AddonSeeder::class,
            ExpenseSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
