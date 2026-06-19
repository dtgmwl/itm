<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@it.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        
        User::updateOrCreate(
            ['email' => 'guest@it.local'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }
}
