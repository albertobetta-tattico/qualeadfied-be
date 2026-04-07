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
                'leads_trend' => $this->trend($leadsThisMonth, $leadsLastMonth),
                'orders_this_month' => $ordersThisMonth,
                'orders_trend' => $this->trend($ordersThisMonth, $ordersLastMonth),
                'revenue_this_month' => round((float) $revenueThisMonth, 2),
                'revenue_trend' => $this->trend((float) $revenueThisMonth, (float) $revenueLastMonth),
                'new_clients_this_month' => $newClientsThisMonth,
                'new_clients_trend' => $this->trend($newClientsThisMonth, $newClientsLastMonth),
            ],
            'recent_leads' => $recentLeads,
            'recent_orders' => $recentOrders,
            'top_categories' => $topCategories,
        ]]);
    }

    /**
     * Calcola il trend percentuale tra il valore corrente e quello precedente.
     * Ritorna null quando non c'è una baseline (mese precedente a 0): il frontend
     * mostrerà "—" o un'etichetta dedicata invece di un fuorviante 0%.
     */
    private function trend(int|float $current, int|float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
