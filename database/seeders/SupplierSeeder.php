<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'first_name' => 'Mohamed',
            'last_name' => 'Ali',
            'email' => 'mohamed.ali@example.com',
            'phone' => '01001234567',
            'address' => '10 El-Tahrir St, Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
