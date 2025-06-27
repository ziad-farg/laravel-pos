<?php

namespace Database\Seeders;

use App\Models\SaleReturn;
use App\Enums\SaleReturnType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SaleReturnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SaleReturn::create([
            'order_id'          => 1,
            'type'           => SaleReturnType::FullReturn->value, // إذا كنت تستخدم Enum
            'return_date'       => now()->subDays(2),
            'total_refund_amount' => 500.00,
            'user_id'           => 1,
            'notes'             => 'Full return for order due to customer dissatisfaction.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }
}
