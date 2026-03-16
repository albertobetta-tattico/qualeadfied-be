<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\UserLead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\AcquisitionMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientTrialController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $profile = $request->user()->clientProfile;

        if (!$profile) {
            return response()->json(['data' => [
                'enabled' => false,
                'leads_remaining' => 0,
                'leads_total' => 0,
                'leads_claimed' => 0,
            ]]);
        }

        $leadsClaimed = UserLead::where('user_id', $request->user()->id)
            ->where('acquisition_type', AcquisitionType::FreeTrial)
            ->count();

        $leadsTotal = $leadsClaimed + $profile->free_trial_leads_remaining;

        return response()->json(['data' => [
            'enabled' => $profile->free_trial_enabled,
            'leads_remaining' => $profile->free_trial_leads_remaining,
            'leads_total' => $leadsTotal,
            'leads_claimed' => $leadsClaimed,
        ]]);
    }

    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|exists:leads,id',
        ]);

        $user = $request->user();
        $profile = $user->clientProfile;

        if (!$profile || !$profile->free_trial_enabled) {
            return response()->json(['message' => 'Free trial not available'], 403);
        }

        $leadIds = $request->input('lead_ids');

        if (count($leadIds) > $profile->free_trial_leads_remaining) {
            return response()->json([
                'message' => 'Not enough free trial leads remaining',
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

        DB::transaction(function () use ($user, $profile, $leadIds) {
            // Create free trial order
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(
                Order::whereYear('created_at', date('Y'))->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'type' => OrderType::FreeTrial,
                'payment_method' => PaymentMethod::Free,
                'subtotal' => 0,
                'vat_rate' => 22,
                'vat_amount' => 0,
                'total' => 0,
                'status' => OrderStatus::Completed,
                'paid_at' => Carbon::now(),
            ]);

            foreach ($leadIds as $leadId) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'lead_id' => $leadId,
                    'acquisition_mode' => AcquisitionMode::Free,
                    'unit_price' => 0,
                    'quantity' => 1,
                    'line_total' => 0,
                ]);

                UserLead::create([
                    'user_id' => $user->id,
                    'lead_id' => $leadId,
                    'order_id' => $order->id,
                    'acquisition_type' => AcquisitionType::FreeTrial,
                    'purchase_price' => 0,
                    'contact_status' => ContactStatus::New,
                    'purchased_at' => Carbon::now(),
                ]);
            }

            $profile->decrement('free_trial_leads_remaining', count($leadIds));
        });

        return response()->json(['message' => 'Trial leads claimed successfully']);
    }
}
