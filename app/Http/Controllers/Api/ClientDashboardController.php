<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLead;
use App\Models\Order;
use App\Models\UserPackage;
use App\Enums\OrderStatus;
use App\Enums\ContactStatus;
use App\Enums\PackageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClientDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();

        $totalLeads = UserLead::where('user_id', $user->id)->count();
        $leadsThisMonth = UserLead::where('user_id', $user->id)
            ->where('purchased_at', '>=', $startOfMonth)
            ->count();

        $totalSpent = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::Paid)
            ->sum('total');
        $spentThisMonth = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::Paid)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');

        $convertedLeads = UserLead::where('user_id', $user->id)
            ->where('contact_status', ContactStatus::Converted)
            ->count();
        $conversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 1)
            : 0;

        $activePackages = UserPackage::where('user_id', $user->id)
            ->where('status', PackageStatus::Active)
            ->count();

        $profile = $user->clientProfile;
        $freeTrialRemaining = $profile ? $profile->free_trial_leads_remaining : 0;

        return response()->json(['data' => [
            'total_leads_purchased' => $totalLeads,
            'leads_this_month' => $leadsThisMonth,
            'total_spent' => round((float) $totalSpent, 2),
            'spent_this_month' => round((float) $spentThisMonth, 2),
            'conversion_rate' => $conversionRate,
            'active_packages' => $activePackages,
            'free_trial_remaining' => $freeTrialRemaining,
        ]]);
    }

    public function recentLeads(Request $request): JsonResponse
    {
        $user = $request->user();

        $recentLeads = UserLead::where('user_id', $user->id)
            ->with(['lead.categories', 'lead.province'])
            ->orderByDesc('purchased_at')
            ->limit(5)
            ->get()
            ->map(function ($userLead) {
                return [
                    'id' => $userLead->id,
                    'name' => $userLead->lead
                        ? $userLead->lead->first_name . ' ' . $userLead->lead->last_name
                        : '',
                    'email' => $userLead->lead?->email ?? '',
                    'categories' => $userLead->lead?->categories->pluck('name')->toArray() ?? [],
                    'province' => $userLead->lead?->province?->code ?? '',
                    'status' => $userLead->contact_status,
                    'acquisition_type' => $userLead->acquisition_type,
                    'purchased_at' => $userLead->purchased_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $recentLeads]);
    }

    public function recentOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $recentOrders = Order::where('user_id', $user->id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->order_number,
                    'items_count' => $order->items_count,
                    'amount' => round((float) $order->total, 2),
                    'status' => $order->status,
                    'date' => $order->created_at->toISOString(),
                ];
            });

        return response()->json(['data' => $recentOrders]);
    }
}
