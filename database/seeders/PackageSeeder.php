<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Seed the packages table with 6 packages.
     */
    public function run(): void
    {
        Package::create([
            'name' => 'Starter Fotovoltaico',
            'description' => 'Pacchetto base per iniziare con lead fotovoltaico. Include 5 lead esclusivi e 10 condivisi.',
            'category_ids' => [1],
            'exclusive_lead_quantity' => 5,
            'exclusive_price' => 150.00,
            'shared_lead_quantity' => 10,
            'shared_price' => 120.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Package::create([
            'name' => 'Pro Infissi',
            'description' => 'Pacchetto professionale per aziende di infissi e serramenti. 10 lead esclusivi e 20 condivisi.',
            'category_ids' => [2],
            'exclusive_lead_quantity' => 10,
            'exclusive_price' => 250.00,
            'shared_lead_quantity' => 20,
            'shared_price' => 200.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Package::create([
            'name' => 'Combo Clima',
            'description' => 'Pacchetto combinato climatizzazione e caldaie. Ideale per installatori multisettore.',
            'category_ids' => [3, 4],
            'exclusive_lead_quantity' => 8,
            'exclusive_price' => 200.00,
            'shared_lead_quantity' => 15,
            'shared_price' => 130.00,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Package::create([
            'name' => 'Enterprise Ristrutturazioni',
            'description' => 'Pacchetto enterprise per grandi imprese di ristrutturazione. Lead esclusivi di alta qualita.',
            'category_ids' => [6],
            'exclusive_lead_quantity' => 15,
            'exclusive_price' => 600.00,
            'shared_lead_quantity' => 10,
            'shared_price' => 200.00,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        Package::create([
            'name' => 'Green Energy Pack',
            'description' => 'Pacchetto completo per aziende del settore energia verde. Copre fotovoltaico, pompe di calore ed efficienza energetica.',
            'category_ids' => [1, 5, 7],
            'exclusive_lead_quantity' => 12,
            'exclusive_price' => 380.00,
            'shared_lead_quantity' => 25,
            'shared_price' => 300.00,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Package::create([
            'name' => 'Trial Pack',
            'description' => 'Pacchetto di prova per nuovi clienti. Piccolo quantitativo di lead a prezzo ridotto.',
            'category_ids' => [1, 2, 3],
            'exclusive_lead_quantity' => 3,
            'exclusive_price' => 75.00,
            'shared_lead_quantity' => 5,
            'shared_price' => 50.00,
            'is_active' => true,
            'sort_order' => 6,
        ]);
    }
}
