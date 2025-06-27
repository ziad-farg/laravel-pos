<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payment::create([
            'amount' => 150.00,
            'order_id' => 1,
            'user_id' => 1,
            'payment_method' => 'cash',
            'till_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
