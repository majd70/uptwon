<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RestaurantSettingSeeder::class);

        User::query()->firstOrCreate(
            ['email' => 'admin@uptown.test'],
            [
                'name' => 'Uptown Admin',
                'password' => Hash::make('password'),
            ]
        );
    }
}
