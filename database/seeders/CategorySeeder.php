<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories table.
     */
    public function run(): void
    {
        Category::updateOrCreate(
            ['slug' => 'scooter-disabili'],
            [
                'name' => 'Scooter disabili',
                'description' => 'Scooter elettrici per persone con disabilità',
                'max_shares' => 3,
                'is_active' => true,
                'sort_order' => 1,
                'custom_fields' => null,
            ]
        );

        Category::updateOrCreate(
            ['slug' => 'microcar'],
            [
                'name' => 'Microcar',
                'description' => 'Microcar e veicoli compatti',
                'max_shares' => 3,
                'is_active' => true,
                'sort_order' => 2,
                'custom_fields' => null,
            ]
        );
    }
}
