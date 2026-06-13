<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seeds the admin account and a few staff accounts.
     * All accounts use the default factory password ("password").
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Kape Martian Admin', 'email' => 'admin@kapemartian.com'],
            ['name' => 'Mary Grace Puyot', 'email' => 'marygrace@kapemartian.com'],
            ['name' => 'Jefferson Tabucol', 'email' => 'jefferson@kapemartian.com'],
        ];

        foreach ($users as $user) {
            User::factory()->create($user);
        }
    }
}
