<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            ProvinceSeeder::class,
            CategorySeeder::class,
            CategoryPriceSeeder::class,
            LeadSourceSeeder::class,
            UserSeeder::class,
            PackageSeeder::class,
            LeadSeeder::class,
            OrderSeeder::class,
            InvoiceSeeder::class,
            TransactionSeeder::class,
            LeadSaleSeeder::class,
            SystemSettingSeeder::class,
            AdminActivityLogSeeder::class,
        ]);
    }
}
