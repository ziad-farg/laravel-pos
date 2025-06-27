<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\DiscountType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::create([
            'customer_id' => 1,
            'user_id' => 1,
            'invoice_discount_type' => DiscountType::Fixed->value,
            'invoice_discount_value' => 10.00,
            'status' => OrderStatus::Completed->value,
            'returned_amount' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
