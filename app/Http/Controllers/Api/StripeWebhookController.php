<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\UserLead;
use App\Models\UserPackage;
use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PackageStatus;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if ($webhookSecret) {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            $event = json_decode($payload);
        }

        switch ($event->type ?? null) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            default:
                Log::info('Stripe webhook: unhandled event type', ['type' => $event->type ?? 'unknown']);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        $order = Order::where('payment_id', $paymentIntent->id)
            ->where('status', OrderStatus::Pending)
            ->first();

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $paymentIntent) {
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => Carbon::now(),
            ]);

            // Create transaction record if not already created
            $existingTransaction = Transaction::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if (!$existingTransaction) {
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
            }

            if ($order->type === OrderType::Package) {
                $this->fulfillPackageOrder($order);
            } else {
                $this->fulfillSingleOrder($order);
            }
        });

        Log::info('Stripe webhook: payment succeeded', ['order_id' => $order->id]);
    }

    private function handlePaymentIntentFailed(object $paymentIntent): void
    {
        $order = Order::where('payment_id', $paymentIntent->id)
            ->where('status', OrderStatus::Pending)
            ->first();

        if (!$order) {
            return;
        }

        $order->update(['status' => OrderStatus::Failed]);

        Transaction::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'payment_type' => $order->payment_method->value === 'card'
                ? TransactionPaymentType::Card
                : TransactionPaymentType::SepaDebit,
            'amount' => $order->total,
            'currency' => 'eur',
            'status' => TransactionStatus::Failed,
            'failure_code' => $paymentIntent->last_payment_error->code ?? null,
            'failure_message' => $paymentIntent->last_payment_error->message ?? null,
            'processed_at' => Carbon::now(),
        ]);

        Log::warning('Stripe webhook: payment failed', ['order_id' => $order->id]);
    }

    private function fulfillPackageOrder(Order $order): void
    {
        // Check if UserPackage already created (idempotency)
        if (UserPackage::where('order_id', $order->id)->exists()) {
            return;
        }

        $orderItem = $order->items()->whereNotNull('package_id')->first();
        if (!$orderItem) {
            return;
        }

        $package = Package::find($orderItem->package_id);
        if (!$package) {
            return;
        }

        UserPackage::create([
            'user_id' => $order->user_id,
            'package_id' => $package->id,
            'order_id' => $order->id,
            'package_name' => $package->name,
            'category_id' => $package->category_ids[0] ?? null,
            'total_leads' => $package->exclusive_lead_quantity + $package->shared_lead_quantity,
            'exclusive_leads_total' => $package->exclusive_lead_quantity,
            'exclusive_leads_used' => 0,
            'shared_leads_total' => $package->shared_lead_quantity,
            'shared_leads_used' => 0,
            'status' => PackageStatus::Active,
            'purchased_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }

    private function fulfillSingleOrder(Order $order): void
    {
        // Check if leads already assigned (idempotency)
        if (UserLead::where('order_id', $order->id)->exists()) {
            return;
        }

        $orderItems = $order->items()->whereNotNull('lead_id')->get();
        foreach ($orderItems as $item) {
            $acquisitionType = $item->acquisition_mode->value === 'exclusive'
                ? AcquisitionType::Exclusive
                : AcquisitionType::Shared;

            UserLead::create([
                'user_id' => $order->user_id,
                'lead_id' => $item->lead_id,
                'order_id' => $order->id,
                'acquisition_type' => $acquisitionType,
                'purchase_price' => $item->unit_price,
                'contact_status' => ContactStatus::New,
                'purchased_at' => Carbon::now(),
            ]);
        }

        // Clear cart items
        \App\Models\CartItem::where('user_id', $order->user_id)->delete();
    }
}
