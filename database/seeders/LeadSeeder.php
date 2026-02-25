<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Seed the leads table with 80 realistic Italian leads.
     */
    public function run(): void
    {
        // Request texts by category
        $requestTexts = [
            1 => [ // Fotovoltaico
                'Vorrei installare un impianto fotovoltaico sul tetto della mia abitazione di circa 120 mq. Attualmente spendo circa 200 euro al mese di bolletta.',
                'Richiedo preventivo per impianto fotovoltaico 6kW con accumulo per villetta bifamiliare.',
                'Interessato a pannelli solari per capannone industriale, superficie disponibile 500 mq.',
                'Desidero informazioni su impianto fotovoltaico con batteria di accumulo per autoconsumo.',
                'Vorrei un preventivo per impianto solare da 3kW per appartamento con terrazzo esposto a sud.',
                'Cerco installatore per fotovoltaico residenziale con sistema di monitoraggio. Tetto a falda, orientamento sud-est.',
                'Richiesta preventivo per impianto fotovoltaico 10kW per azienda agricola con possibilita di scambio sul posto.',
                'Interessato a impianto fotovoltaico con pompa di calore integrata per nuova costruzione.',
                'Vorrei valutare installazione pannelli solari su tetto piano di condominio, circa 300 mq disponibili.',
                'Preventivo per impianto fotovoltaico 4.5kW con ottimizzatori e accumulo 10kWh.',
                'Richiedo sopralluogo per impianto fotovoltaico su villetta singola, bolletta attuale circa 150 euro/mese.',
                'Interessato a soluzione fotovoltaica per bed & breakfast, consumo annuo circa 8000 kWh.',
                'Vorrei installare pannelli solari e colonnina di ricarica per auto elettrica.',
                'Richiesta informazioni per impianto fotovoltaico con cessione del credito.',
                'Preventivo urgente per impianto solare 6kW su nuova costruzione in fase di completamento.',
                'Desidero confrontare offerte per fotovoltaico residenziale da 5kW con e senza accumulo.',
                'Cerco azienda certificata per installazione impianto fotovoltaico su tetto in eternit da bonificare.',
                'Richiedo preventivo per ampliamento impianto fotovoltaico esistente da 3kW a 6kW.',
                'Interessato a impianto fotovoltaico per riscaldamento acqua sanitaria e integrazione riscaldamento.',
                'Vorrei informazioni su incentivi disponibili e preventivo per impianto da 4kW.',
            ],
            2 => [ // Infissi
                'Sostituzione di 8 finestre in PVC con doppio vetro per appartamento, attualmente ho infissi in legno vecchi di 30 anni.',
                'Preventivo per 5 porte-finestre in alluminio taglio termico per villa al mare.',
                'Cerco serramenti in legno-alluminio per ristrutturazione completa, 12 finestre e 3 portoncini.',
                'Vorrei sostituire gli infissi del condominio, 24 finestre totali, richiesta preventivo.',
                'Interessato a infissi in PVC con triplo vetro per zona climatica E.',
                'Richiesta preventivo per portoncino blindato e 6 finestre in PVC bianco.',
                'Vorrei installare zanzariere e persiane su 10 finestre gia esistenti in PVC.',
                'Sostituzione completa serramenti villa bifamiliare: 15 finestre e 4 porte-finestre in alluminio.',
                'Preventivo per infissi scorrevoli in alluminio per veranda di 20 mq.',
                'Cerco preventivo per sostituzione 7 finestre con cassonetti coibentati e avvolgibili motorizzati.',
                'Richiedo preventivo per finestre antirumore per appartamento su strada trafficata.',
                'Installazione porte-finestre a libro in alluminio per apertura su giardino.',
                'Sostituzione infissi con detrazione fiscale 50%, 10 finestre in PVC effetto legno.',
                'Preventivo per infissi in legno massello per casa di campagna, 6 finestre arco.',
                'Cerco serramenti performanti per casa passiva, triplo vetro basso emissivo.',
            ],
            3 => [ // Climatizzazione
                'Installazione climatizzatore multisplit per appartamento 100 mq, 3 unita interne.',
                'Preventivo per impianto di climatizzazione centralizzato per ufficio 200 mq.',
                'Cerco condizionatore inverter per monolocale 35 mq, classe energetica A+++.',
                'Richiesta preventivo per climatizzazione negozio di 80 mq con pompa di calore.',
                'Vorrei installare un sistema di climatizzazione canalizzata per villa su due livelli.',
                'Sostituzione vecchio condizionatore con nuovo modello inverter per camera da letto.',
                'Preventivo per impianto VRF per uffici open space, circa 400 mq.',
                'Installazione condizionatori in 3 stanze con unica unita esterna, zona climatica C.',
                'Richiedo preventivo per deumidificatore e condizionatore integrato per taverna.',
                'Climatizzazione completa villetta 150 mq su due piani, preferenza per sistema con Wi-Fi.',
            ],
            4 => [ // Caldaie
                'Sostituzione caldaia a gas tradizionale con caldaia a condensazione per appartamento.',
                'Preventivo per caldaia a condensazione 24kW con bollitore per villa con 2 bagni.',
                'Cerco installatore per caldaia a condensazione da esterno con scarico a tetto.',
                'Richiesta preventivo per sostituzione caldaia condominio centralizzato, 12 appartamenti.',
                'Vorrei sostituire la caldaia con modello a condensazione classe A, attuale da 20 anni.',
                'Preventivo per caldaia murale a condensazione con cronotermostato smart.',
                'Installazione caldaia a condensazione con solare termico per acqua calda sanitaria.',
                'Sostituzione urgente caldaia rotta, serve preventivo rapido per caldaia a condensazione 28kW.',
            ],
            5 => [ // Pompe di Calore
                'Installazione pompa di calore aria-acqua per riscaldamento e raffrescamento villa 180 mq.',
                'Preventivo per pompa di calore ibrida con caldaia a condensazione di backup.',
                'Cerco pompa di calore per sostituzione impianto a gasolio in casa di montagna.',
                'Richiesta preventivo per pompa di calore aria-aria per appartamento 90 mq.',
                'Vorrei installare pompa di calore geotermica per nuova costruzione.',
                'Preventivo per sistema a pompa di calore con riscaldamento a pavimento.',
                'Sostituzione caldaia con pompa di calore full electric per villa bifamiliare.',
            ],
            6 => [ // Ristrutturazioni
                'Ristrutturazione completa appartamento 80 mq: bagno, cucina, pavimenti e impianti.',
                'Preventivo per ristrutturazione bagno con doccia walk-in e sanitari sospesi.',
                'Cerco impresa per ristrutturazione villa anni 70, circa 200 mq su due livelli.',
                'Richiesta preventivo per ristrutturazione cucina con spostamento impianti idraulici.',
                'Ristrutturazione integrale bilocale da affittare: 55 mq, chiavi in mano.',
                'Preventivo per ristrutturazione attico con terrazzo e creazione open space.',
                'Cerco impresa per ristrutturazione locale commerciale 120 mq da adibire a ristorante.',
                'Ristrutturazione con superbonus: cappotto termico, infissi, caldaia per villetta bifamiliare.',
                'Preventivo per demolizione e ricostruzione tramezzi con nuova distribuzione spazi.',
                'Ristrutturazione bagno e rifacimento impianto elettrico per appartamento anni 60.',
            ],
            7 => [ // Efficienza Energetica
                'Richiesta diagnosi energetica per condominio anni 80, 20 unita abitative.',
                'Preventivo per riqualificazione energetica completa villetta singola classe G.',
                'Cerco tecnico per APE e consulenza su interventi di efficientamento energetico.',
                'Vorrei migliorare la classe energetica del mio appartamento da F a C.',
                'Consulenza per accesso a incentivi efficienza energetica per piccola impresa.',
            ],
            8 => [ // Isolamento Termico
                'Preventivo per cappotto termico esterno condominio 4 piani, superficie circa 800 mq.',
                'Cerco azienda per isolamento termico sottotetto con fibra di cellulosa.',
                'Richiesta preventivo per insufflaggio intercapedine per villetta anni 70.',
                'Isolamento termico solaio di copertura con pannelli in polistirene espanso.',
                'Preventivo per cappotto termico interno per appartamento in centro storico con vincoli.',
            ],
        ];

        // Italian first and last names
        $firstNames = [
            'Marco', 'Luca', 'Giuseppe', 'Antonio', 'Paolo', 'Giovanni', 'Francesco', 'Alessandro',
            'Andrea', 'Stefano', 'Roberto', 'Massimo', 'Davide', 'Matteo', 'Simone',
            'Laura', 'Francesca', 'Maria', 'Giulia', 'Chiara', 'Anna', 'Sara', 'Elena',
            'Valentina', 'Federica', 'Silvia', 'Paola', 'Claudia', 'Monica', 'Roberta',
        ];

        $lastNames = [
            'Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci',
            'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Mancini', 'Costa',
            'Giordano', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri', 'Fontana', 'Santoro',
            'Mariani', 'Rinaldi', 'Caruso', 'Ferrara', 'Galli', 'Martini', 'Leone',
        ];

        // Big city province IDs (weighted towards these)
        // Based on insert order: 1=TO, 10=MI, 15=VE, 17=PD, 25=GE, 29=BO, 39=FI, 56=RM, 63=NA, 68=BA, 82=PA, 85=CT, 93=CA
        $bigCityProvinceIds = [1, 10, 15, 17, 25, 29, 39, 56, 63, 68, 82, 85, 93];

        // Source weights: 1=Sito Web (40%), 2=Facebook (30%), 3=Google (15%), 4=Referral (10%), 5=Manuale (5%)
        $sourceWeights = array_merge(
            array_fill(0, 8, 1),   // Sito Web x8
            array_fill(0, 6, 2),   // Facebook x6
            array_fill(0, 3, 3),   // Google x3
            array_fill(0, 2, 4),   // Referral x2
            array_fill(0, 1, 5),   // Manuale x1
        );

        // Distribution: cat 1=20, 2=15, 3=10, 4=8, 5=7, 6=10, 7=5, 8=5
        $categoryDistribution = [
            1 => 20, 2 => 15, 3 => 10, 4 => 8, 5 => 7, 6 => 10, 7 => 5, 8 => 5,
        ];

        // Status distribution: ~40 free, ~15 sold_exclusive, ~15 sold_shared, ~10 exhausted
        $statusPool = array_merge(
            array_fill(0, 40, 'free'),
            array_fill(0, 15, 'sold_exclusive'),
            array_fill(0, 15, 'sold_shared'),
            array_fill(0, 10, 'exhausted'),
        );
        shuffle($statusPool);

        $leadIndex = 0;

        foreach ($categoryDistribution as $categoryId => $count) {
            $texts = $requestTexts[$categoryId];

            for ($i = 0; $i < $count; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $status = $statusPool[$leadIndex];
                $sourceId = $sourceWeights[array_rand($sourceWeights)];

                // Province: 70% chance big city, 30% random
                if (rand(1, 100) <= 70) {
                    $provinceId = $bigCityProvinceIds[array_rand($bigCityProvinceIds)];
                } else {
                    $provinceId = rand(1, 107);
                }

                // Determine current_shares based on status
                $maxShares = match ($categoryId) {
                    3 => 4,
                    6 => 2,
                    default => 3,
                };

                $currentShares = match ($status) {
                    'free' => 0,
                    'sold_exclusive' => 0,
                    'sold_shared' => rand(1, min(2, $maxShares - 1)),
                    'exhausted' => $maxShares,
                    default => 0,
                };

                // Extra tags: mostly null, some have tags
                $extraTags = null;
                if (rand(1, 5) === 1) {
                    $tagOptions = [
                        ['urgente'],
                        ['grande impianto'],
                        ['urgente', 'grande impianto'],
                        ['nuovo cliente'],
                        ['ricontattare'],
                        ['budget elevato'],
                        ['detrazione fiscale'],
                    ];
                    $extraTags = $tagOptions[array_rand($tagOptions)];
                }

                $emailSlug = strtolower(str_replace(' ', '', $firstName)) . '.' . strtolower(str_replace([' ', "'"], ['', ''], $lastName));

                Lead::create([
                    'category_id' => $categoryId,
                    'province_id' => $provinceId,
                    'source_id' => $sourceId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $emailSlug . rand(10, 99) . '@' . ['gmail.com', 'yahoo.it', 'libero.it', 'hotmail.it', 'outlook.it'][rand(0, 4)],
                    'phone' => '+39 3' . rand(20, 99) . ' ' . rand(1000000, 9999999),
                    'request_text' => $texts[$i % count($texts)],
                    'extra_tags' => $extraTags,
                    'status' => $status,
                    'current_shares' => $currentShares,
                    'generated_at' => now()->subDays(rand(1, 90)),
                    'external_id' => $sourceId <= 3 ? 'EXT-' . strtoupper(substr(md5(rand()), 0, 8)) : null,
                ]);

                $leadIndex++;
            }
        }
    }
}
