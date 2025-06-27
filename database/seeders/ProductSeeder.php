<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Laptop Pro 15',
            'description' => 'High performance laptop with 16GB RAM and 512GB SSD.',
            'barcode' => 'LP15-001',
            'price' => 12000.00,
            'stock' => 50,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
