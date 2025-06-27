<?php

namespace Database\Seeders;

use App\Models\Till;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Till::create([
            'user_id'          => 1,
            'opened_at'        => now()->subDays(5)->startOfDay(),
            'closed_at'        => null,
            'cash_handed_over' => 1500.00,
            'visa_handed_over' => 500.00,
            'shortage'         => 0.00,
            'surplus'          => 0.00,
            'created_at'       => now()->subDays(5)->startOfDay(),
            'updated_at'       => now()->subDays(5)->endOfDay(),
        ]);
    }
}
