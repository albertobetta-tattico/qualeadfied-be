<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserPackage;
use App\Models\Transaction;
use App\Models\UserLead;
use App\Enums\AcquisitionMode;
use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PackageStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionPaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($package) {
                // exclusive_price / shared_price are bundle totals (admin form
                // labels them "Prezzo Totale"), not unit prices. The price the
                // admin sets IS the price the user pays — no automatic discount.
                $exclusiveTotal = (float) $package->exclusive_price;
                $sharedTotal = (float) $package->shared_price;
                $price = round($exclusiveTotal + $sharedTotal, 2);
                $totalLeads = $package->exclusive_lead_quantity + $package->shared_lead_quantity;

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'category_id' => $package->category_ids[0] ?? null,
                    'total_leads' => $totalLeads,
                    'exclusive_leads' => $package->exclusive_lead_quantity,
                    'shared_leads' => $package->shared_lead_quantity,
                    'price' => $price,
                    'discount_percent' => 0,
                    'original_price' => $price,
                    'is_active' => $package->is_active,
                    'valid_days' => 30,
                ];
            });

        return response()->json(['data' => $packages]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
            'payment_method' => 'required|in:card,sepa',
        ]);

        $user = $request->user();
        $package = Package::findOrFail($request->package_id);

        if (!$package->is_active) {
            return response()->json(['message' => 'Package is not available'], 422);
        }

        // exclusive_price / shared_price are bundle totals, not unit prices.
        $subtotal = round((float) $package->exclusive_price + (float) $package->shared_price, 2);
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
                'package_id' => $package->id,
                'type' => 'package',
            ],
        ]);

        // Create order with pending status
        $order = DB::transaction(function () use ($user, $package, $request, $subtotal, $vatRate, $vatAmount, $total, $paymentIntent) {
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(
                Order::whereYear('created_at', date('Y'))->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'type' => OrderType::Package,
                'payment_method' => $request->payment_method === 'card'
                    ? PaymentMethod::Card
                    : PaymentMethod::Sepa,
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => OrderStatus::Pending,
                'payment_id' => $paymentIntent->id,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'package_id' => $package->id,
                'acquisition_mode' => AcquisitionMode::Exclusive,
                'unit_price' => $subtotal,
                'quantity' => 1,
                'line_total' => $subtotal,
            ]);

            return $order;
        });

        return response()->json(['data' => [
            'client_secret' => $paymentIntent->client_secret,
            'amount' => $total,
            'currency' => 'eur',
            'order_id' => $order->id,
            'package_id' => $package->id,
        ]]);
    }

    public function confirmPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $paymentIntentId = $request->payment_intent_id;

        $order = Order::where('user_id', $user->id)
            ->where('payment_id', $paymentIntentId)
            ->where('type', OrderType::Package)
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

        $result = DB::transaction(function () use ($order, $user, $paymentIntent) {
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

            // Get the package from the order item
            $orderItem = $order->items()->whereNotNull('package_id')->first();
            $package = Package::findOrFail($orderItem->package_id);

            $userPackage = UserPackage::create([
                'user_id' => $user->id,
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

            return [
                'order_id' => $order->id,
                'active_package' => [
                    'id' => $userPackage->id,
                    'package_id' => $userPackage->package_id,
                    'user_id' => $userPackage->user_id,
                    'package_name' => $userPackage->package_name,
                    'category_id' => $userPackage->category_id,
                    'total_leads' => $userPackage->total_leads,
                    'exclusive_leads_total' => $userPackage->exclusive_leads_total,
                    'exclusive_leads_used' => $userPackage->exclusive_leads_used,
                    'shared_leads_total' => $userPackage->shared_leads_total,
                    'shared_leads_used' => $userPackage->shared_leads_used,
                    'purchased_at' => $userPackage->purchased_at->toISOString(),
                    'expires_at' => $userPackage->expires_at->toISOString(),
                    'is_expired' => false,
                ],
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function selectLeads(Request $request, int $packageId): JsonResponse
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|exists:leads,id',
            'purchase_modes' => 'required|array',
            'purchase_modes.*' => 'required|in:exclusive,shared',
        ]);

        $user = $request->user();
        $userPackage = UserPackage::where('user_id', $user->id)
            ->where('id', $packageId)
            ->where('status', PackageStatus::Active)
            ->firstOrFail();

        $leadIds = $request->input('lead_ids');
        $purchaseModes = $request->input('purchase_modes');

        // Count requested by mode
        $exclusiveRequested = 0;
        $sharedRequested = 0;
        foreach ($leadIds as $leadId) {
            $mode = $purchaseModes[$leadId] ?? 'shared';
            if ($mode === 'exclusive') {
                $exclusiveRequested++;
            } else {
                $sharedRequested++;
            }
        }

        $exclusiveRemaining = $userPackage->exclusive_leads_total - $userPackage->exclusive_leads_used;
        $sharedRemaining = $userPackage->shared_leads_total - $userPackage->shared_leads_used;

        if ($exclusiveRequested > $exclusiveRemaining || $sharedRequested > $sharedRemaining) {
            return response()->json([
                'message' => 'Not enough leads remaining in package',
            ], 422);
        }

        // Check leads aren't already owned
        $alreadyOwned = UserLead::where('user_id', $user->id)
            ->whereIn('lead_id', $leadIds)
            ->pluck('lead_id')
            ->toArray();

        if (!empty($alreadyOwned)) {
            return response()->json([
                'message' => 'Some leads are already in your portfolio',
            ], 422);
        }

        DB::transaction(function () use ($user, $userPackage, $leadIds, $purchaseModes) {
            foreach ($leadIds as $leadId) {
                $mode = $purchaseModes[$leadId] ?? 'shared';
                $acquisitionType = $mode === 'exclusive'
                    ? AcquisitionType::Exclusive
                    : AcquisitionType::Shared;

                UserLead::create([
                    'user_id' => $user->id,
                    'lead_id' => $leadId,
                    'order_id' => $userPackage->order_id,
                    'acquisition_type' => $acquisitionType,
                    'purchase_price' => 0,
                    'contact_status' => ContactStatus::New,
                    'purchased_at' => Carbon::now(),
                ]);

                if ($mode === 'exclusive') {
                    $userPackage->increment('exclusive_leads_used');
                } else {
                    $userPackage->increment('shared_leads_used');
                }
            }

            // Check if package is exhausted
            $userPackage->refresh();
            $totalRemaining = ($userPackage->exclusive_leads_total - $userPackage->exclusive_leads_used)
                + ($userPackage->shared_leads_total - $userPackage->shared_leads_used);
            if ($totalRemaining <= 0) {
                $userPackage->update(['status' => PackageStatus::Exhausted]);
            }
        });

        return response()->json(['message' => 'Leads selected successfully']);
    }
}
