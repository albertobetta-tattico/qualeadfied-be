<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Seed the transactions table with 1 transaction per non-free order.
     */
    public function run(): void
    {
        // Get all non-free orders
        $orders = Order::where('type', '!=', 'free_trial')
            ->orderBy('id')
            ->get();

        // Map user_id to a consistent stripe customer id
        $customerIds = [];

        foreach ($orders as $order) {
            $userId = $order->user_id;

            if (!isset($customerIds[$userId])) {
                $customerIds[$userId] = 'cus_test_' . substr(md5('user_' . $userId), 0, 14);
            }

            $paymentIntentId = 'pi_test_' . substr(md5('order_' . $order->id . '_pi'), 0, 14);
            $chargeId = 'ch_test_' . substr(md5('order_' . $order->id . '_ch'), 0, 14);
            $paymentMethodId = 'pm_test_' . substr(md5('order_' . $order->id . '_pm'), 0, 14);

            $paymentType = $order->payment_method->value === 'sepa' ? 'sepa_debit' : 'card';

            $status = match ($order->status->value) {
                'paid', 'completed' => 'succeeded',
                'failed' => 'failed',
                'pending' => 'pending',
                'processing' => 'processing',
                default => 'pending',
            };

            $failureCode = null;
            $failureMessage = null;
            if ($status === 'failed') {
                $failureCodes = [
                    ['card_declined', 'La carta e stata rifiutata. Contattare la banca emittente.'],
                    ['insufficient_funds', 'Fondi insufficienti sulla carta.'],
                    ['expired_card', 'La carta risulta scaduta.'],
                ];
                $failure = $failureCodes[array_rand($failureCodes)];
                $failureCode = $failure[0];
                $failureMessage = $failure[1];
            }

            $processedAt = in_array($status, ['succeeded', 'failed'])
                ? ($order->paid_at ?? now()->subDays(rand(1, 30)))
                : null;

            Transaction::create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $status === 'succeeded' || $status === 'failed' ? $chargeId : null,
                'stripe_customer_id' => $customerIds[$userId],
                'stripe_payment_method_id' => $paymentMethodId,
                'payment_type' => $paymentType,
                'amount' => $order->total,
                'currency' => 'EUR',
                'status' => $status,
                'stripe_response' => [
                    'id' => $paymentIntentId,
                    'object' => 'payment_intent',
                    'amount' => (int) ($order->total * 100),
                    'currency' => 'eur',
                    'status' => $status,
                ],
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'processed_at' => $processedAt,
            ]);
        }
    }
}
