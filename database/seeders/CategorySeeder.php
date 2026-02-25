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
        Category::create([
            'name' => 'Fotovoltaico',
            'slug' => 'fotovoltaico',
            'description' => 'Impianti fotovoltaici per la produzione di energia solare',
            'max_shares' => 3,
            'is_active' => true,
            'sort_order' => 1,
            'custom_fields' => [
                ['name' => 'potenza_impianto', 'label' => 'Potenza Impianto (kW)', 'type' => 'number'],
                ['name' => 'superficie_tetto', 'label' => 'Superficie Tetto (mq)', 'type' => 'number'],
                ['name' => 'tipo_impianto', 'label' => 'Tipo Impianto', 'type' => 'select', 'options' => ['residenziale', 'commerciale', 'industriale']],
            ],
        ]);

        Category::create([
            'name' => 'Infissi e Serramenti',
            'slug' => 'infissi-serramenti',
            'description' => 'Sostituzione e installazione di infissi e serramenti',
            'max_shares' => 3,
            'is_active' => true,
            'sort_order' => 2,
            'custom_fields' => [
                ['name' => 'tipo_infisso', 'label' => 'Tipo Infisso', 'type' => 'select', 'options' => ['finestra', 'porta-finestra', 'portoncino']],
                ['name' => 'numero_finestre', 'label' => 'Numero Finestre', 'type' => 'number'],
                ['name' => 'materiale', 'label' => 'Materiale', 'type' => 'select', 'options' => ['pvc', 'alluminio', 'legno', 'legno-alluminio']],
            ],
        ]);

        Category::create([
            'name' => 'Climatizzazione',
            'slug' => 'climatizzazione',
            'description' => 'Sistemi di climatizzazione e condizionamento',
            'max_shares' => 4,
            'is_active' => true,
            'sort_order' => 3,
            'custom_fields' => null,
        ]);

        Category::create([
            'name' => 'Caldaie',
            'slug' => 'caldaie',
            'description' => 'Installazione e sostituzione caldaie a condensazione',
            'max_shares' => 3,
            'is_active' => true,
            'sort_order' => 4,
            'custom_fields' => null,
        ]);

        Category::create([
            'name' => 'Pompe di Calore',
            'slug' => 'pompe-di-calore',
            'description' => 'Installazione pompe di calore per riscaldamento e raffrescamento',
            'max_shares' => 3,
            'is_active' => true,
            'sort_order' => 5,
            'custom_fields' => null,
        ]);

        Category::create([
            'name' => 'Ristrutturazioni',
            'slug' => 'ristrutturazioni',
            'description' => 'Ristrutturazioni edilizie complete e parziali',
            'max_shares' => 2,
            'is_active' => true,
            'sort_order' => 6,
            'custom_fields' => null,
        ]);

        Category::create([
            'name' => 'Efficienza Energetica',
            'slug' => 'efficienza-energetica',
            'description' => 'Soluzioni per il miglioramento dell\'efficienza energetica degli edifici',
            'max_shares' => 3,
            'is_active' => true,
            'sort_order' => 7,
            'custom_fields' => null,
        ]);

        Category::create([
            'name' => 'Isolamento Termico',
            'slug' => 'isolamento-termico',
            'description' => 'Cappotto termico e isolamento per edifici',
            'max_shares' => 3,
            'is_active' => false,
            'sort_order' => 8,
            'custom_fields' => null,
        ]);
    }
}
