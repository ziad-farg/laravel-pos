<?php

namespace Database\Seeders;

use App\Models\SaleReturnItem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SaleReturnItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SaleReturnItem::create([
            'sale_return_id'    => 1,
            'product_id'        => 1,
            'quantity'          => 2,
            'price_at_return'   => 95.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
