<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadSale;
use App\Models\Order;
use Illuminate\Database\Seeder;

class LeadSaleSeeder extends Seeder
{
    /**
     * Seed the lead_sales table for sold/exhausted leads.
     */
    public function run(): void
    {
        // Get leads that have been sold or exhausted
        $soldExclusiveLeads = Lead::where('status', 'sold_exclusive')->get();
        $soldSharedLeads = Lead::where('status', 'sold_shared')->get();
        $exhaustedLeads = Lead::where('status', 'exhausted')->get();

        // Get paid/completed orders to link sales to
        $paidOrders = Order::whereIn('status', ['paid', 'completed'])->get();
        $activeUserIds = $paidOrders->pluck('user_id')->unique()->values()->toArray();

        if (empty($activeUserIds)) {
            return;
        }

        // Exclusive leads: 1 sale each
        foreach ($soldExclusiveLeads as $lead) {
            $userId = $activeUserIds[array_rand($activeUserIds)];
            $order = $paidOrders->where('user_id', $userId)->first();

            LeadSale::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'order_id' => $order?->id,
                'user_package_id' => null,
                'mode' => 'exclusive',
                'share_slot' => null,
                'price_paid' => $this->getPriceForCategory($lead->category_id, 'exclusive'),
                'sold_at' => $lead->generated_at ? $lead->generated_at->copy()->addDays(rand(1, 7)) : now()->subDays(rand(1, 30)),
            ]);
        }

        // Shared leads: 1-2 sales each
        foreach ($soldSharedLeads as $lead) {
            $numSales = $lead->current_shares;
            $usedUserIds = [];

            for ($slot = 1; $slot <= $numSales; $slot++) {
                // Pick a different user for each slot
                $availableUsers = array_diff($activeUserIds, $usedUserIds);
                if (empty($availableUsers)) {
                    $availableUsers = $activeUserIds;
                }

                $userId = $availableUsers[array_rand($availableUsers)];
                $usedUserIds[] = $userId;

                $order = $paidOrders->where('user_id', $userId)->first();

                LeadSale::create([
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'order_id' => $order?->id,
                    'user_package_id' => null,
                    'mode' => 'shared',
                    'share_slot' => $slot,
                    'price_paid' => $this->getPriceForCategory($lead->category_id, 'shared'),
                    'sold_at' => $lead->generated_at ? $lead->generated_at->copy()->addDays(rand(1, 7)) : now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Exhausted leads: max_shares sales each
        foreach ($exhaustedLeads as $lead) {
            $maxShares = $lead->current_shares;
            $usedUserIds = [];

            for ($slot = 1; $slot <= $maxShares; $slot++) {
                $availableUsers = array_diff($activeUserIds, $usedUserIds);
                if (empty($availableUsers)) {
                    $availableUsers = $activeUserIds;
                }

                $userId = $availableUsers[array_rand($availableUsers)];
                $usedUserIds[] = $userId;

                $order = $paidOrders->where('user_id', $userId)->first();

                LeadSale::create([
                    'lead_id' => $lead->id,
                    'user_id' => $userId,
                    'order_id' => $order?->id,
                    'user_package_id' => null,
                    'mode' => 'shared',
                    'share_slot' => $slot,
                    'price_paid' => $this->getPriceForCategory($lead->category_id, 'shared'),
                    'sold_at' => $lead->generated_at ? $lead->generated_at->copy()->addDays(rand(1, 5)) : now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    /**
     * Get price for a category based on mode.
     */
    private function getPriceForCategory(int $categoryId, string $mode): float
    {
        $prices = [
            1 => ['exclusive' => 35.00, 'shared' => 15.00],
            2 => ['exclusive' => 28.00, 'shared' => 12.00],
            3 => ['exclusive' => 32.00, 'shared' => 10.00],
            4 => ['exclusive' => 22.00, 'shared' => 10.00],
            5 => ['exclusive' => 30.00, 'shared' => 13.00],
            6 => ['exclusive' => 45.00, 'shared' => 25.00],
            7 => ['exclusive' => 38.00, 'shared' => 16.00],
            8 => ['exclusive' => 35.00, 'shared' => 15.00],
        ];

        return $prices[$categoryId][$mode] ?? 15.00;
    }
}
