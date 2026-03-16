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

        $subtotal = $cartItems->sum('price');
        $vatRate = 22;
        $vatAmount = round($subtotal * $vatRate / 100, 2);
        $total = round($subtotal + $vatAmount, 2);

        // Create Stripe PaymentIntent
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($total * 100),
            'currency' => 'eur',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        // Create order with pending status
        $order = DB::transaction(function () use ($user, $request, $cartItems, $subtotal, $vatRate, $vatAmount, $total, $paymentIntent) {
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(
                Order::whereYear('created_at', date('Y'))->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

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

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
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
                $acquisitionMode = $cartItem->purchase_mode->value === 'exclusive'
                    ? AcquisitionMode::Exclusive
                    : AcquisitionMode::Shared;

                OrderItem::create([
                    'order_id' => $order->id,
                    'lead_id' => $cartItem->lead_id,
                    'acquisition_mode' => $acquisitionMode,
                    'unit_price' => $cartItem->price,
                    'quantity' => 1,
                    'line_total' => $cartItem->price,
                ]);
            }

            return $order;
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

            // Create transaction record
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

            // Create UserLead for each order item
            $orderItems = $order->items()->with('lead')->get();
            foreach ($orderItems as $item) {
                if (!$item->lead_id) continue;

                $acquisitionType = $item->acquisition_mode->value === 'exclusive'
                    ? AcquisitionType::Exclusive
                    : AcquisitionType::Shared;

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

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();
        });

        return response()->json(['data' => [
            'order_id' => $order->id,
        ]]);
    }
}
