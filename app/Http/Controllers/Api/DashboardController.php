<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // KPIs
        $totalLeads = Lead::count();
        $leadsThisMonth = Lead::where('created_at', '>=', $startOfMonth)->count();
        $leadsLastMonth = Lead::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $ordersThisMonth = Order::where('created_at', '>=', $startOfMonth)->count();
        $ordersLastMonth = Order::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $revenueThisMonth = Order::where('status', 'paid')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');
        $revenueLastMonth = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');

        $newClientsThisMonth = User::where('role', 'client')
            ->where('created_at', '>=', $startOfMonth)->count();
        $newClientsLastMonth = User::where('role', 'client')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Recent data
        $recentLeads = Lead::with(['category', 'province'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentOrders = Order::with('user.clientProfile')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Top categories by lead count
        $topCategories = Category::withCount('leads')
            ->where('is_active', true)
            ->orderByDesc('leads_count')
            ->limit(5)
            ->get();

        return response()->json(['data' => [
            'kpis' => [
                'total_leads' => $totalLeads,
                'leads_this_month' => $leadsThisMonth,
                'leads_trend' => $leadsLastMonth > 0
                    ? round(($leadsThisMonth - $leadsLastMonth) / $leadsLastMonth * 100, 1)
                    : 0,
                'orders_this_month' => $ordersThisMonth,
                'orders_trend' => $ordersLastMonth > 0
                    ? round(($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth * 100, 1)
                    : 0,
                'revenue_this_month' => round((float) $revenueThisMonth, 2),
                'revenue_trend' => $revenueLastMonth > 0
                    ? round(($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100, 1)
                    : 0,
                'new_clients_this_month' => $newClientsThisMonth,
                'new_clients_trend' => $newClientsLastMonth > 0
                    ? round(($newClientsThisMonth - $newClientsLastMonth) / $newClientsLastMonth * 100, 1)
                    : 0,
            ],
            'recent_leads' => $recentLeads,
            'recent_orders' => $recentOrders,
            'top_categories' => $topCategories,
        ]]);
    }
}
