<?php

namespace Database\Seeders;

use App\Models\CategoryPrice;
use Illuminate\Database\Seeder;

class CategoryPriceSeeder extends Seeder
{
    /**
     * Seed the category_prices table with current and historical prices.
     */
    public function run(): void
    {
        $prices = [
            // category_id => [exclusive, shared_prices, max_shares]
            1 => [35.00, ['slot_1' => 15.00, 'slot_2' => 15.00, 'slot_3' => 15.00]],
            2 => [28.00, ['slot_1' => 12.00, 'slot_2' => 12.00, 'slot_3' => 12.00]],
            3 => [32.00, ['slot_1' => 10.00, 'slot_2' => 10.00, 'slot_3' => 10.00, 'slot_4' => 10.00]],
            4 => [22.00, ['slot_1' => 10.00, 'slot_2' => 10.00, 'slot_3' => 10.00]],
            5 => [30.00, ['slot_1' => 13.00, 'slot_2' => 13.00, 'slot_3' => 13.00]],
            6 => [45.00, ['slot_1' => 25.00, 'slot_2' => 25.00]],
            7 => [38.00, ['slot_1' => 16.00, 'slot_2' => 16.00, 'slot_3' => 16.00]],
        ];

        foreach ($prices as $categoryId => $priceData) {
            // Historical price (expired 30 days ago)
            CategoryPrice::create([
                'category_id' => $categoryId,
                'exclusive_price' => round($priceData[0] * 0.85, 2),
                'shared_prices' => array_map(fn ($p) => round($p * 0.85, 2), $priceData[1]),
                'valid_from' => now()->subMonths(6),
                'valid_to' => now()->subDays(30),
            ]);

            // Current price (no expiry)
            CategoryPrice::create([
                'category_id' => $categoryId,
                'exclusive_price' => $priceData[0],
                'shared_prices' => $priceData[1],
                'valid_from' => now()->subDays(30),
                'valid_to' => null,
            ]);
        }
    }
}
