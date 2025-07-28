<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'eliteShop Admin',
            'email' => 'admin@eliteshop.com',
            'password' => Hash::make("AdminPass@123"),
            'role' => 'super_admin'
        ]);
    }
}
