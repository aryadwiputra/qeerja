<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->asSuperAdmin()->create([
            'name' => 'Admin',
            'email' => 'admin@taska.test',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            TciPersonnelSeeder::class,
            TciProjectSeeder::class,
            TciTaskSeeder::class,
        ]);
    }
}
