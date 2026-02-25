<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Seed the provinces table with all 107 Italian provinces.
     */
    public function run(): void
    {
        Province::insert([
            // Piemonte (8)
            ['name' => 'Torino', 'code' => 'TO', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Alessandria', 'code' => 'AL', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Asti', 'code' => 'AT', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Biella', 'code' => 'BI', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Cuneo', 'code' => 'CN', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Novara', 'code' => 'NO', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Verbano-Cusio-Ossola', 'code' => 'VB', 'region' => 'Piemonte', 'is_active' => true],
            ['name' => 'Vercelli', 'code' => 'VC', 'region' => 'Piemonte', 'is_active' => true],

            // Valle d'Aosta (1)
            ['name' => 'Aosta', 'code' => 'AO', 'region' => "Valle d'Aosta", 'is_active' => true],

            // Lombardia (12)
            ['name' => 'Milano', 'code' => 'MI', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Bergamo', 'code' => 'BG', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Brescia', 'code' => 'BS', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Como', 'code' => 'CO', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Cremona', 'code' => 'CR', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Lecco', 'code' => 'LC', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Lodi', 'code' => 'LO', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Mantova', 'code' => 'MN', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Monza e Brianza', 'code' => 'MB', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Pavia', 'code' => 'PV', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Sondrio', 'code' => 'SO', 'region' => 'Lombardia', 'is_active' => true],
            ['name' => 'Varese', 'code' => 'VA', 'region' => 'Lombardia', 'is_active' => true],

            // Trentino-Alto Adige (2)
            ['name' => 'Trento', 'code' => 'TN', 'region' => 'Trentino-Alto Adige', 'is_active' => true],
            ['name' => 'Bolzano', 'code' => 'BZ', 'region' => 'Trentino-Alto Adige', 'is_active' => true],

            // Veneto (7)
            ['name' => 'Venezia', 'code' => 'VE', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Belluno', 'code' => 'BL', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Padova', 'code' => 'PD', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Rovigo', 'code' => 'RO', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Treviso', 'code' => 'TV', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Verona', 'code' => 'VR', 'region' => 'Veneto', 'is_active' => true],
            ['name' => 'Vicenza', 'code' => 'VI', 'region' => 'Veneto', 'is_active' => true],

            // Friuli Venezia Giulia (4)
            ['name' => 'Trieste', 'code' => 'TS', 'region' => 'Friuli Venezia Giulia', 'is_active' => true],
            ['name' => 'Gorizia', 'code' => 'GO', 'region' => 'Friuli Venezia Giulia', 'is_active' => true],
            ['name' => 'Pordenone', 'code' => 'PN', 'region' => 'Friuli Venezia Giulia', 'is_active' => true],
            ['name' => 'Udine', 'code' => 'UD', 'region' => 'Friuli Venezia Giulia', 'is_active' => true],

            // Liguria (4)
            ['name' => 'Genova', 'code' => 'GE', 'region' => 'Liguria', 'is_active' => true],
            ['name' => 'Imperia', 'code' => 'IM', 'region' => 'Liguria', 'is_active' => true],
            ['name' => 'La Spezia', 'code' => 'SP', 'region' => 'Liguria', 'is_active' => true],
            ['name' => 'Savona', 'code' => 'SV', 'region' => 'Liguria', 'is_active' => true],

            // Emilia-Romagna (9)
            ['name' => 'Bologna', 'code' => 'BO', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Ferrara', 'code' => 'FE', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => "Forl\u{EC}-Cesena", 'code' => 'FC', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Modena', 'code' => 'MO', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Parma', 'code' => 'PR', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Piacenza', 'code' => 'PC', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Ravenna', 'code' => 'RA', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Reggio Emilia', 'code' => 'RE', 'region' => 'Emilia-Romagna', 'is_active' => true],
            ['name' => 'Rimini', 'code' => 'RN', 'region' => 'Emilia-Romagna', 'is_active' => true],

            // Toscana (10)
            ['name' => 'Firenze', 'code' => 'FI', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Arezzo', 'code' => 'AR', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Grosseto', 'code' => 'GR', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Livorno', 'code' => 'LI', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Lucca', 'code' => 'LU', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Massa-Carrara', 'code' => 'MS', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Pisa', 'code' => 'PI', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Pistoia', 'code' => 'PT', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Prato', 'code' => 'PO', 'region' => 'Toscana', 'is_active' => true],
            ['name' => 'Siena', 'code' => 'SI', 'region' => 'Toscana', 'is_active' => true],

            // Umbria (2)
            ['name' => 'Perugia', 'code' => 'PG', 'region' => 'Umbria', 'is_active' => true],
            ['name' => 'Terni', 'code' => 'TR', 'region' => 'Umbria', 'is_active' => true],

            // Marche (5)
            ['name' => 'Ancona', 'code' => 'AN', 'region' => 'Marche', 'is_active' => true],
            ['name' => 'Ascoli Piceno', 'code' => 'AP', 'region' => 'Marche', 'is_active' => true],
            ['name' => 'Fermo', 'code' => 'FM', 'region' => 'Marche', 'is_active' => true],
            ['name' => 'Macerata', 'code' => 'MC', 'region' => 'Marche', 'is_active' => true],
            ['name' => 'Pesaro e Urbino', 'code' => 'PU', 'region' => 'Marche', 'is_active' => true],

            // Lazio (5)
            ['name' => 'Roma', 'code' => 'RM', 'region' => 'Lazio', 'is_active' => true],
            ['name' => 'Frosinone', 'code' => 'FR', 'region' => 'Lazio', 'is_active' => true],
            ['name' => 'Latina', 'code' => 'LT', 'region' => 'Lazio', 'is_active' => true],
            ['name' => 'Rieti', 'code' => 'RI', 'region' => 'Lazio', 'is_active' => true],
            ['name' => 'Viterbo', 'code' => 'VT', 'region' => 'Lazio', 'is_active' => true],

            // Abruzzo (4)
            ["name" => "L'Aquila", 'code' => 'AQ', 'region' => 'Abruzzo', 'is_active' => true],
            ['name' => 'Chieti', 'code' => 'CH', 'region' => 'Abruzzo', 'is_active' => true],
            ['name' => 'Pescara', 'code' => 'PE', 'region' => 'Abruzzo', 'is_active' => true],
            ['name' => 'Teramo', 'code' => 'TE', 'region' => 'Abruzzo', 'is_active' => true],

            // Molise (2)
            ['name' => 'Campobasso', 'code' => 'CB', 'region' => 'Molise', 'is_active' => true],
            ['name' => 'Isernia', 'code' => 'IS', 'region' => 'Molise', 'is_active' => true],

            // Campania (5)
            ['name' => 'Napoli', 'code' => 'NA', 'region' => 'Campania', 'is_active' => true],
            ['name' => 'Avellino', 'code' => 'AV', 'region' => 'Campania', 'is_active' => true],
            ['name' => 'Benevento', 'code' => 'BN', 'region' => 'Campania', 'is_active' => true],
            ['name' => 'Caserta', 'code' => 'CE', 'region' => 'Campania', 'is_active' => true],
            ['name' => 'Salerno', 'code' => 'SA', 'region' => 'Campania', 'is_active' => true],

            // Puglia (6)
            ['name' => 'Bari', 'code' => 'BA', 'region' => 'Puglia', 'is_active' => true],
            ['name' => 'Barletta-Andria-Trani', 'code' => 'BT', 'region' => 'Puglia', 'is_active' => true],
            ['name' => 'Brindisi', 'code' => 'BR', 'region' => 'Puglia', 'is_active' => true],
            ['name' => 'Foggia', 'code' => 'FG', 'region' => 'Puglia', 'is_active' => true],
            ['name' => 'Lecce', 'code' => 'LE', 'region' => 'Puglia', 'is_active' => true],
            ['name' => 'Taranto', 'code' => 'TA', 'region' => 'Puglia', 'is_active' => true],

            // Basilicata (2)
            ['name' => 'Potenza', 'code' => 'PZ', 'region' => 'Basilicata', 'is_active' => true],
            ['name' => 'Matera', 'code' => 'MT', 'region' => 'Basilicata', 'is_active' => true],

            // Calabria (5)
            ['name' => 'Catanzaro', 'code' => 'CZ', 'region' => 'Calabria', 'is_active' => true],
            ['name' => 'Cosenza', 'code' => 'CS', 'region' => 'Calabria', 'is_active' => true],
            ['name' => 'Crotone', 'code' => 'KR', 'region' => 'Calabria', 'is_active' => true],
            ['name' => 'Reggio Calabria', 'code' => 'RC', 'region' => 'Calabria', 'is_active' => true],
            ['name' => 'Vibo Valentia', 'code' => 'VV', 'region' => 'Calabria', 'is_active' => true],

            // Sicilia (9)
            ['name' => 'Palermo', 'code' => 'PA', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Agrigento', 'code' => 'AG', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Caltanissetta', 'code' => 'CL', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Catania', 'code' => 'CT', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Enna', 'code' => 'EN', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Messina', 'code' => 'ME', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Ragusa', 'code' => 'RG', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Siracusa', 'code' => 'SR', 'region' => 'Sicilia', 'is_active' => true],
            ['name' => 'Trapani', 'code' => 'TP', 'region' => 'Sicilia', 'is_active' => true],

            // Sardegna (5)
            ['name' => 'Cagliari', 'code' => 'CA', 'region' => 'Sardegna', 'is_active' => true],
            ['name' => 'Nuoro', 'code' => 'NU', 'region' => 'Sardegna', 'is_active' => true],
            ['name' => 'Oristano', 'code' => 'OR', 'region' => 'Sardegna', 'is_active' => true],
            ['name' => 'Sassari', 'code' => 'SS', 'region' => 'Sardegna', 'is_active' => true],
            ['name' => 'Sud Sardegna', 'code' => 'SU', 'region' => 'Sardegna', 'is_active' => true],
        ]);
    }
}
