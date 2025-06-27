<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\PurchaseItem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PurchaseItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseItem::create([
            'purchase_id'       => 1,
            'product_id'        => 1,
            'quantity'          => 10,
            'cost_price'        => 45.00,
            'item_discount_type' => DiscountType::Percentage->value,
            'item_discount_value' => 5.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
