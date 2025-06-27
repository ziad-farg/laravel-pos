<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Gamal',
            'email' => 'ahmed.gamal@example.com',
            'phone' => '01012345678',
            'address' => '123 Main St, Cairo',
            'user_id' => 1,
        ]);
    }
}
