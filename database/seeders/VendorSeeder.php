<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Joe Vendor',
            'email' => 'vendor@eliteshop.com',
            'password' => Hash::make("VendorPass@123"),
            'role' => 'vendor'
        ]);
    }
}
