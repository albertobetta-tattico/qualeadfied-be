<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserLead;
use App\Models\Transaction;
use App\Enums\AcquisitionMode;
use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Enums\TransactionPaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'billing_address' => 'required|string',
            'billing_city' => 'required|string',
            'billing_province' => 'required|string|size:2',
            'billing_zip' => 'required|string|size:5',
            'billing_country' => 'required|string',
            'sdi_code' => 'nullable|string|max:7',
            'pec_email' => 'nullable|email',
            'payment_method' => 'required|in:card,sepa',
            'accept_terms' => 'required|accepted',
        ]);

        $user = $request->user();
        $cartItems = CartItem::where('user_id', $user->id)
            ->with('lead')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        // Update billing data on profile
        $profile = $user->clientProfile;
        if ($profile) {
            $profile->update([
                'billing_address' => $request->billing_address,
                'billing_city' => $request->billing_city,
                'billing_province' => $request->billing_province,
                'billing_zip' => $request->billing_zip,
                'billing_country' => $request->billing_country,
                'sdi_code' => $request->sdi_code,
                'pec_email' => $request->pec_email,
            ]);
        }

        $subtotal = (float) $cartItems->sum('price');
        $vatRate = 22;
        $vatAmount = round($subtotal * $vatRate / 100, 2);
        $total = round($subtotal + $vatAmount, 2);

        $billingSnapshot = [
            'company_name' => $user->clientProfile?->company_name ?? '',
            'vat_number' => $user->clientProfile?->vat_number ?? '',
            'address' => $request->billing_address,
            'city' => $request->billing_city,
            'province' => $request->billing_province,
            'zip' => $request->billing_zip,
            'country' => $request->billing_country,
            'sdi_code' => $request->sdi_code,
            'pec_email' => $request->pec_email,
        ];

        // Free-only cart: bypass Stripe, fulfill immediately
        if ($total <= 0) {
            $order = $this->fulfillFreeOrder($user, $cartItems, $billingSnapshot, $vatRate);

            return response()->json(['data' => [
                'client_secret' => null,
                'amount' => 0,
                'currency' => 'eur',
                'free_order_id' => $order->id,
            ]]);
        }

        // Create Stripe PaymentIntent. Surface missing config as a clean 503
        // (the SDK otherwise throws and Laravel returns a generic 500).
        $stripeSecret = config('services.stripe.secret');
        if (empty($stripeSecret)) {
            return response()->json([
                'message' => 'Servizio di pagamento non configurato. Contatta l\'amministratore.',
            ], 503);
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($total * 100),
            'currency' => 'eur',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        DB::transaction(function () use ($user, $request, $cartItems, $subtotal, $vatRate, $vatAmount, $total, $paymentIntent, $billingSnapshot) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->nextOrderNumber(),
                'type' => OrderType::Single,
                'payment_method' => $request->payment_method === 'card'
                    ? PaymentMethod::Card
                    : PaymentMethod::Sepa,
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => OrderStatus::Pending,
                'billing_snapshot' => $billingSnapshot,
                'payment_id' => $paymentIntent->id,
            ]);

            foreach ($cartItems as $cartItem) {
                $acquisitionMode = $cartItem->is_free_trial
                    ? AcquisitionMode::Free
                    : ($cartItem->purchase_mode->value === 'exclusive'
                        ? AcquisitionMode::Exclusive
                        : AcquisitionMode::Shared);

                OrderItem::create([
                    'order_id' => $order->id,
                    'lead_id' => $cartItem->lead_id,
                    'acquisition_mode' => $acquisitionMode,
                    'unit_price' => $cartItem->price,
                    'quantity' => 1,
                    'line_total' => $cartItem->price,
                    'is_free_trial' => $cartItem->is_free_trial,
                ]);
            }
        });

        return response()->json(['data' => [
            'client_secret' => $paymentIntent->client_secret,
            'amount' => $total,
            'currency' => 'eur',
        ]]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $paymentIntentId = $request->payment_intent_id;

        $order = Order::where('user_id', $user->id)
            ->where('payment_id', $paymentIntentId)
            ->where('status', OrderStatus::Pending)
            ->firstOrFail();

        // Verify with Stripe
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json([
                'message' => 'Payment not yet completed',
            ], 422);
        }

        DB::transaction(function () use ($order, $user, $paymentIntent) {
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => Carbon::now(),
            ]);

            Transaction::create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
                'payment_type' => $order->payment_method->value === 'card'
                    ? TransactionPaymentType::Card
                    : TransactionPaymentType::SepaDebit,
                'amount' => $order->total,
                'currency' => 'eur',
                'status' => TransactionStatus::Succeeded,
                'processed_at' => Carbon::now(),
            ]);

            $orderItems = $order->items()->with('lead')->get();
            $freeTrialCount = 0;
            foreach ($orderItems as $item) {
                if (!$item->lead_id) continue;

                $acquisitionType = $item->is_free_trial
                    ? AcquisitionType::FreeTrial
                    : ($item->acquisition_mode->value === 'exclusive'
                        ? AcquisitionType::Exclusive
                        : AcquisitionType::Shared);

                if ($item->is_free_trial) {
                    $freeTrialCount++;
                }

                UserLead::create([
                    'user_id' => $user->id,
                    'lead_id' => $item->lead_id,
                    'order_id' => $order->id,
                    'acquisition_type' => $acquisitionType,
                    'purchase_price' => $item->unit_price,
                    'contact_status' => ContactStatus::New,
                    'purchased_at' => Carbon::now(),
                ]);
            }

            if ($freeTrialCount > 0 && $user->clientProfile) {
                $user->clientProfile->decrement('free_trial_leads_remaining', $freeTrialCount);
            }

            CartItem::where('user_id', $user->id)->delete();
        });

        return response()->json(['data' => [
            'order_id' => $order->id,
        ]]);
    }

    private function fulfillFreeOrder($user, $cartItems, array $billingSnapshot, int $vatRate): Order
    {
        return DB::transaction(function () use ($user, $cartItems, $billingSnapshot, $vatRate) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->nextOrderNumber(),
                'type' => OrderType::FreeTrial,
                'payment_method' => PaymentMethod::Free,
                'subtotal' => 0,
                'vat_rate' => $vatRate,
                'vat_amount' => 0,
                'total' => 0,
                'status' => OrderStatus::Completed,
                'billing_snapshot' => $billingSnapshot,
                'paid_at' => Carbon::now(),
            ]);

            $freeTrialCount = 0;
            foreach ($cartItems as $cartItem) {
                $isFree = (bool) $cartItem->is_free_trial;
                if ($isFree) {
                    $freeTrialCount++;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'lead_id' => $cartItem->lead_id,
                    'acquisition_mode' => $isFree ? AcquisitionMode::Free : AcquisitionMode::Shared,
                    'unit_price' => 0,
                    'quantity' => 1,
                    'line_total' => 0,
                    'is_free_trial' => $isFree,
                ]);

                UserLead::create([
                    'user_id' => $user->id,
                    'lead_id' => $cartItem->lead_id,
                    'order_id' => $order->id,
                    'acquisition_type' => $isFree ? AcquisitionType::FreeTrial : AcquisitionType::Shared,
                    'purchase_price' => 0,
                    'contact_status' => ContactStatus::New,
                    'purchased_at' => Carbon::now(),
                ]);
            }

            if ($freeTrialCount > 0 && $user->clientProfile) {
                $user->clientProfile->decrement('free_trial_leads_remaining', $freeTrialCount);
            }

            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });
    }

    private function nextOrderNumber(): string
    {
        return 'ORD-' . date('Y') . '-' . str_pad(
            (string) (Order::whereYear('created_at', date('Y'))->count() + 1),
            5,
            '0',
            STR_PAD_LEFT
        );
    }
}
