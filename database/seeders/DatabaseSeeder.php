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
        // I dati iniziali (admin, utente test, categorie, province) sono in:
        // database/migrations/2026_03_31_000002_seed_initial_data.php

        $this->call([
            // CategoryPriceSeeder::class,
            // PackageSeeder::class,
            // LeadSeeder::class,
            // OrderSeeder::class,
            // InvoiceSeeder::class,
            // TransactionSeeder::class,
            // LeadSaleSeeder::class,
            // SystemSettingSeeder::class,
            // AdminActivityLogSeeder::class,
            // TestUserDashboardSeeder::class,
        ]);
    }
}
