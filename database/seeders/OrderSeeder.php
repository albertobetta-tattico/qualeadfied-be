<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed the orders table with 20 orders and their order items.
     */
    public function run(): void
    {
        // Define 20 orders: [user_id, type, payment_method, status, item_count, items_config]
        $orders = [
            // 10 paid orders
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 1, 'mode' => 'exclusive', 'price' => 35.00],
                ['lead_id' => 2, 'mode' => 'shared', 'price' => 15.00],
            ]],
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 3, 'mode' => 'exclusive', 'price' => 35.00],
            ]],
            ['user_id' => 2, 'type' => 'single', 'payment_method' => 'sepa', 'status' => 'paid', 'items' => [
                ['lead_id' => 21, 'mode' => 'exclusive', 'price' => 28.00],
                ['lead_id' => 22, 'mode' => 'shared', 'price' => 12.00],
                ['lead_id' => 23, 'mode' => 'shared', 'price' => 12.00],
            ]],
            ['user_id' => 3, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 36, 'mode' => 'exclusive', 'price' => 32.00],
            ]],
            ['user_id' => 4, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 46, 'mode' => 'exclusive', 'price' => 22.00],
                ['lead_id' => 47, 'mode' => 'shared', 'price' => 10.00],
            ]],
            ['user_id' => 5, 'type' => 'package', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['package_id' => 2, 'mode' => 'exclusive', 'price' => 250.00, 'qty' => 1],
            ]],
            ['user_id' => 5, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 24, 'mode' => 'exclusive', 'price' => 28.00],
            ]],
            ['user_id' => 6, 'type' => 'package', 'payment_method' => 'sepa', 'status' => 'paid', 'items' => [
                ['package_id' => 5, 'mode' => 'exclusive', 'price' => 380.00, 'qty' => 1],
            ]],
            ['user_id' => 7, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 61, 'mode' => 'exclusive', 'price' => 45.00],
                ['lead_id' => 62, 'mode' => 'shared', 'price' => 25.00],
            ]],
            ['user_id' => 3, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 37, 'mode' => 'shared', 'price' => 10.00],
                ['lead_id' => 38, 'mode' => 'shared', 'price' => 10.00],
            ]],

            // 3 completed orders
            ['user_id' => 1, 'type' => 'package', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['package_id' => 1, 'mode' => 'exclusive', 'price' => 150.00, 'qty' => 1],
            ]],
            ['user_id' => 2, 'type' => 'single', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['lead_id' => 25, 'mode' => 'exclusive', 'price' => 28.00],
                ['lead_id' => 26, 'mode' => 'exclusive', 'price' => 28.00],
            ]],
            ['user_id' => 7, 'type' => 'package', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['package_id' => 4, 'mode' => 'exclusive', 'price' => 600.00, 'qty' => 1],
            ]],

            // 3 pending orders
            ['user_id' => 3, 'type' => 'single', 'payment_method' => 'card', 'status' => 'pending', 'items' => [
                ['lead_id' => 39, 'mode' => 'exclusive', 'price' => 32.00],
            ]],
            ['user_id' => 4, 'type' => 'single', 'payment_method' => 'sepa', 'status' => 'pending', 'items' => [
                ['lead_id' => 48, 'mode' => 'shared', 'price' => 10.00],
                ['lead_id' => 49, 'mode' => 'shared', 'price' => 10.00],
            ]],
            ['user_id' => 6, 'type' => 'single', 'payment_method' => 'card', 'status' => 'pending', 'items' => [
                ['lead_id' => 54, 'mode' => 'exclusive', 'price' => 30.00],
            ]],

            // 2 processing orders
            ['user_id' => 5, 'type' => 'single', 'payment_method' => 'card', 'status' => 'processing', 'items' => [
                ['lead_id' => 27, 'mode' => 'exclusive', 'price' => 28.00],
            ]],
            ['user_id' => 7, 'type' => 'package', 'payment_method' => 'sepa', 'status' => 'processing', 'items' => [
                ['package_id' => 3, 'mode' => 'exclusive', 'price' => 200.00, 'qty' => 1],
            ]],

            // 2 failed orders
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'failed', 'items' => [
                ['lead_id' => 4, 'mode' => 'exclusive', 'price' => 35.00],
            ]],
            ['user_id' => 6, 'type' => 'single', 'payment_method' => 'card', 'status' => 'failed', 'items' => [
                ['lead_id' => 55, 'mode' => 'shared', 'price' => 13.00],
                ['lead_id' => 56, 'mode' => 'shared', 'price' => 13.00],
            ]],

            // 3 free_trial orders (replacing some of the singles above to reach 3 free_trials)
        ];

        // Replace the last 3 single orders' types with free_trial to meet distribution requirements
        // Actually, let me restructure to have exactly: 12 single, 5 package, 3 free_trial = 20 total
        // Current: 12 single + 5 package = 17, need 3 free_trial
        // Let me add 3 free_trial and remove 3 from above

        // Reset and build properly
        $ordersData = [
            // --- 10 paid ---
            // 1: single paid
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 1, 'mode' => 'exclusive', 'price' => 35.00, 'qty' => 1],
                ['lead_id' => 2, 'mode' => 'shared', 'price' => 15.00, 'qty' => 1],
            ]],
            // 2: single paid
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 3, 'mode' => 'exclusive', 'price' => 35.00, 'qty' => 1],
            ]],
            // 3: single paid (sepa)
            ['user_id' => 2, 'type' => 'single', 'payment_method' => 'sepa', 'status' => 'paid', 'items' => [
                ['lead_id' => 21, 'mode' => 'exclusive', 'price' => 28.00, 'qty' => 1],
                ['lead_id' => 22, 'mode' => 'shared', 'price' => 12.00, 'qty' => 1],
            ]],
            // 4: single paid
            ['user_id' => 3, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 36, 'mode' => 'exclusive', 'price' => 32.00, 'qty' => 1],
            ]],
            // 5: package paid
            ['user_id' => 5, 'type' => 'package', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['package_id' => 2, 'mode' => 'exclusive', 'price' => 250.00, 'qty' => 1],
            ]],
            // 6: package paid (sepa)
            ['user_id' => 6, 'type' => 'package', 'payment_method' => 'sepa', 'status' => 'paid', 'items' => [
                ['package_id' => 5, 'mode' => 'exclusive', 'price' => 380.00, 'qty' => 1],
            ]],
            // 7: single paid
            ['user_id' => 4, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 46, 'mode' => 'exclusive', 'price' => 22.00, 'qty' => 1],
            ]],
            // 8: single paid
            ['user_id' => 7, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 61, 'mode' => 'exclusive', 'price' => 45.00, 'qty' => 1],
                ['lead_id' => 62, 'mode' => 'shared', 'price' => 25.00, 'qty' => 1],
            ]],
            // 9: free_trial paid
            ['user_id' => 2, 'type' => 'free_trial', 'payment_method' => 'free', 'status' => 'paid', 'items' => [
                ['lead_id' => 5, 'mode' => 'free', 'price' => 0.00, 'qty' => 1],
            ]],
            // 10: single paid
            ['user_id' => 5, 'type' => 'single', 'payment_method' => 'card', 'status' => 'paid', 'items' => [
                ['lead_id' => 24, 'mode' => 'exclusive', 'price' => 28.00, 'qty' => 1],
            ]],

            // --- 3 completed ---
            // 11: package completed
            ['user_id' => 1, 'type' => 'package', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['package_id' => 1, 'mode' => 'exclusive', 'price' => 150.00, 'qty' => 1],
            ]],
            // 12: single completed
            ['user_id' => 2, 'type' => 'single', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['lead_id' => 25, 'mode' => 'exclusive', 'price' => 28.00, 'qty' => 1],
                ['lead_id' => 26, 'mode' => 'exclusive', 'price' => 28.00, 'qty' => 1],
            ]],
            // 13: package completed
            ['user_id' => 7, 'type' => 'package', 'payment_method' => 'card', 'status' => 'completed', 'items' => [
                ['package_id' => 4, 'mode' => 'exclusive', 'price' => 600.00, 'qty' => 1],
            ]],

            // --- 3 pending ---
            // 14: single pending
            ['user_id' => 3, 'type' => 'single', 'payment_method' => 'card', 'status' => 'pending', 'items' => [
                ['lead_id' => 39, 'mode' => 'exclusive', 'price' => 32.00, 'qty' => 1],
            ]],
            // 15: free_trial pending
            ['user_id' => 4, 'type' => 'free_trial', 'payment_method' => 'free', 'status' => 'pending', 'items' => [
                ['lead_id' => 6, 'mode' => 'free', 'price' => 0.00, 'qty' => 1],
            ]],
            // 16: single pending
            ['user_id' => 6, 'type' => 'single', 'payment_method' => 'card', 'status' => 'pending', 'items' => [
                ['lead_id' => 54, 'mode' => 'exclusive', 'price' => 30.00, 'qty' => 1],
            ]],

            // --- 2 processing ---
            // 17: single processing
            ['user_id' => 5, 'type' => 'single', 'payment_method' => 'card', 'status' => 'processing', 'items' => [
                ['lead_id' => 27, 'mode' => 'exclusive', 'price' => 28.00, 'qty' => 1],
            ]],
            // 18: package processing
            ['user_id' => 7, 'type' => 'package', 'payment_method' => 'sepa', 'status' => 'processing', 'items' => [
                ['package_id' => 3, 'mode' => 'exclusive', 'price' => 200.00, 'qty' => 1],
            ]],

            // --- 2 failed ---
            // 19: single failed
            ['user_id' => 1, 'type' => 'single', 'payment_method' => 'card', 'status' => 'failed', 'items' => [
                ['lead_id' => 4, 'mode' => 'exclusive', 'price' => 35.00, 'qty' => 1],
            ]],
            // 20: free_trial failed
            ['user_id' => 8, 'type' => 'free_trial', 'payment_method' => 'free', 'status' => 'failed', 'items' => [
                ['lead_id' => 7, 'mode' => 'free', 'price' => 0.00, 'qty' => 1],
            ]],
        ];

        $vatRate = 22.00;

        // Cache billing snapshots
        $billingSnapshots = [];
        $profiles = ClientProfile::all()->keyBy('user_id');

        foreach ($ordersData as $index => $orderData) {
            $orderNumber = sprintf('ORD-2026-%05d', $index + 1);
            $userId = $orderData['user_id'];

            // Build billing snapshot from profile
            if (!isset($billingSnapshots[$userId]) && $profiles->has($userId)) {
                $profile = $profiles[$userId];
                $billingSnapshots[$userId] = [
                    'company_name' => $profile->company_name,
                    'vat_number' => $profile->vat_number,
                    'address' => $profile->billing_address,
                    'city' => $profile->billing_city,
                    'province' => $profile->billing_province,
                    'zip' => $profile->billing_zip,
                    'country' => $profile->billing_country,
                    'sdi_code' => $profile->sdi_code,
                    'pec_email' => $profile->pec_email,
                ];
            }

            // Calculate subtotal from items
            $subtotal = 0;
            foreach ($orderData['items'] as $item) {
                $qty = $item['qty'] ?? 1;
                $subtotal += $item['price'] * $qty;
            }

            // For free_trial orders, everything is 0
            $isFree = $orderData['type'] === 'free_trial';
            $actualSubtotal = $isFree ? 0.00 : $subtotal;
            $vatAmount = $isFree ? 0.00 : round($actualSubtotal * $vatRate / 100, 2);
            $total = $isFree ? 0.00 : round($actualSubtotal + $vatAmount, 2);

            $isPaid = in_array($orderData['status'], ['paid', 'completed']);
            $paidAt = $isPaid ? now()->subDays(rand(1, 60)) : null;
            $paymentId = $isPaid && !$isFree ? 'pi_test_' . substr(md5(rand()), 0, 16) : null;

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'type' => $orderData['type'],
                'payment_method' => $orderData['payment_method'],
                'subtotal' => $actualSubtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => $orderData['status'],
                'billing_snapshot' => $billingSnapshots[$userId] ?? null,
                'invoice_number' => null,
                'invoice_url' => null,
                'payment_id' => $paymentId,
                'paid_at' => $paidAt,
            ]);

            // Create order items
            foreach ($orderData['items'] as $item) {
                $qty = $item['qty'] ?? 1;
                $lineTotal = $isFree ? 0.00 : $item['price'] * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'lead_id' => $item['lead_id'] ?? null,
                    'package_id' => $item['package_id'] ?? null,
                    'acquisition_mode' => $item['mode'],
                    'unit_price' => $isFree ? 0.00 : $item['price'],
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ]);
            }
        }
    }
}
