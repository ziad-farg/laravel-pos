<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Purchase::create([
            'user_id'               => 1,
            'supplier_id'           => 1,
            'invoice_number'        => '2001',
            'total_amount'          => 1500.00,
            'paid_amount'           => 1500.00,
            'payment_status'        => PaymentStatus::Paid->value,
            'purchase_date'         => '2025-06-20',
            'notes'                 => 'Initial stock purchase of electronics.',
            'invoice_discount_type' => DiscountType::Fixed->value,
            'invoice_discount_value' => 50.00,
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);
    }
}
