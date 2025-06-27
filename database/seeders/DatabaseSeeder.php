<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SettingsSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            TillSeeder::class,
            PurchaseSeeder::class,
            OrderSeeder::class,
            PurchaseItemSeeder::class,
            OrderItemSeeder::class,
            PaymentSeeder::class,
            SaleReturnSeeder::class,
            SaleReturnItemSeeder::class,
        ]);
    }
}
