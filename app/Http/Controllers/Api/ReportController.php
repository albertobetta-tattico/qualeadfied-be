<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadSale;
use App\Models\Order;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Sales statistics with month-over-month change percentages and 12-month chart data.
     */
    public function sales(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Current month stats
        $currentRevenue = (float) Order::where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total');

        $currentOrders = Order::where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        // Previous month stats
        $previousRevenue = (float) Order::where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total');

        $previousOrders = Order::where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        // Totals (all time for paid orders)
        $totalRevenue = (float) Order::where('status', OrderStatus::Paid)->sum('total');
        $totalOrders = Order::where('status', OrderStatus::Paid)->count();
        $totalLeadsSold = LeadSale::count();
        $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Change percentages (current month vs previous month)
        $revenueChangePercent = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : ($currentRevenue > 0 ? 100.0 : 0.0);

        $ordersChangePercent = $previousOrders > 0
            ? round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1)
            : ($currentOrders > 0 ? 100.0 : 0.0);

        // Chart data: last 12 months
        $chartStartDate = $now->copy()->subMonths(11)->startOfMonth();

        $monthlyData = Order::where('status', OrderStatus::Paid)
            ->where('created_at', '>=', $chartStartDate)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        $monthlyOrders = Order::where('status', OrderStatus::Paid)
            ->where('created_at', '>=', $chartStartDate)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as orders')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('orders', 'month')
            ->toArray();

        // Build labels and datasets for exactly 12 months
        $labels = [];
        $revenueDataset = [];
        $ordersDataset = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthKey = $now->copy()->subMonths($i)->format('Y-m');
            $monthLabel = $now->copy()->subMonths($i)->translatedFormat('M Y');

            $labels[] = $monthLabel;
            $revenueDataset[] = round((float) ($monthlyData[$monthKey] ?? 0), 2);
            $ordersDataset[] = (int) ($monthlyOrders[$monthKey] ?? 0);
        }

        return response()->json([
            'data' => [
                'stats' => [
                    'total_revenue' => round($totalRevenue, 2),
                    'total_orders' => $totalOrders,
                    'total_leads_sold' => $totalLeadsSold,
                    'average_order_value' => $averageOrderValue,
                    'revenue_change_percent' => $revenueChangePercent,
                    'orders_change_percent' => $ordersChangePercent,
                ],
                'chart' => [
                    'labels' => $labels,
                    'datasets' => [
                        'revenue' => $revenueDataset,
                        'orders' => $ordersDataset,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Category performance: leads count, sales, availability, revenue, sell-through rate.
     */
    public function categories(Request $request): JsonResponse
    {
        // Aggregate lead_sales data per category in a single query
        $salesData = DB::table('lead_sales')
            ->join('leads', 'lead_sales.lead_id', '=', 'leads.id')
            ->selectRaw('leads.category_id, COUNT(lead_sales.id) as leads_sold, SUM(lead_sales.price_paid) as revenue')
            ->groupBy('leads.category_id')
            ->get()
            ->keyBy('category_id');

        // Leads available (status = free) per category
        $availableData = Lead::where('status', LeadStatus::Free)
            ->selectRaw('category_id, COUNT(*) as available_count')
            ->groupBy('category_id')
            ->pluck('available_count', 'category_id');

        $categories = Category::withCount('leads')
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($salesData, $availableData) {
                $totalLeads = $category->leads_count;
                $leadsSold = isset($salesData[$category->id]) ? (int) $salesData[$category->id]->leads_sold : 0;
                $leadsAvailable = (int) ($availableData[$category->id] ?? 0);
                $revenue = isset($salesData[$category->id]) ? round((float) $salesData[$category->id]->revenue, 2) : 0;
                $sellThroughRate = $totalLeads > 0 ? round(($leadsSold / $totalLeads) * 100, 1) : 0;

                return [
                    'name' => $category->name,
                    'total_leads' => $totalLeads,
                    'leads_sold' => $leadsSold,
                    'leads_available' => $leadsAvailable,
                    'revenue' => $revenue,
                    'sell_through_rate' => $sellThroughRate,
                ];
            });

        return response()->json(['data' => $categories->values()]);
    }

    /**
     * Geographic statistics: per-province and per-region aggregations with leads, sales and revenue.
     */
    public function geographic(Request $request): JsonResponse
    {
        // Province-level data: total leads, leads sold (via lead_sales), revenue, and top category
        $provinces = DB::table('provinces')
            ->leftJoin('leads', 'provinces.id', '=', 'leads.province_id')
            ->leftJoin('lead_sales', 'leads.id', '=', 'lead_sales.lead_id')
            ->where('provinces.is_active', true)
            ->selectRaw('
                provinces.id as province_id,
                provinces.code as province_code,
                provinces.name as province_name,
                provinces.region,
                COUNT(DISTINCT leads.id) as total_leads,
                COUNT(DISTINCT lead_sales.id) as leads_sold,
                COALESCE(SUM(lead_sales.price_paid), 0) as revenue
            ')
            ->groupBy('provinces.id', 'provinces.code', 'provinces.name', 'provinces.region')
            ->orderByDesc('total_leads')
            ->get();

        // Top category per province: find the category with the most leads for each province
        $topCategories = DB::table('leads')
            ->join('categories', 'leads.category_id', '=', 'categories.id')
            ->selectRaw('leads.province_id, categories.name as category_name, COUNT(*) as lead_count')
            ->groupBy('leads.province_id', 'categories.name')
            ->get()
            ->groupBy('province_id')
            ->map(function ($group) {
                return $group->sortByDesc('lead_count')->first()->category_name ?? null;
            });

        $provincesData = $provinces->map(function ($province) use ($topCategories) {
            return [
                'province_code' => $province->province_code,
                'province_name' => $province->province_name,
                'region' => $province->region,
                'top_category' => $topCategories[$province->province_id] ?? null,
                'total_leads' => (int) $province->total_leads,
                'leads_sold' => (int) $province->leads_sold,
                'revenue' => round((float) $province->revenue, 2),
            ];
        })->values();

        // Region-level aggregation
        $regions = DB::table('provinces')
            ->leftJoin('leads', 'provinces.id', '=', 'leads.province_id')
            ->leftJoin('lead_sales', 'leads.id', '=', 'lead_sales.lead_id')
            ->where('provinces.is_active', true)
            ->selectRaw('
                provinces.region,
                COUNT(DISTINCT provinces.id) as provinces_count,
                COUNT(DISTINCT leads.id) as total_leads,
                COUNT(DISTINCT lead_sales.id) as leads_sold,
                COALESCE(SUM(lead_sales.price_paid), 0) as revenue
            ')
            ->groupBy('provinces.region')
            ->orderByDesc('total_leads')
            ->get()
            ->map(function ($region) {
                return [
                    'region' => $region->region,
                    'provinces_count' => (int) $region->provinces_count,
                    'total_leads' => (int) $region->total_leads,
                    'leads_sold' => (int) $region->leads_sold,
                    'revenue' => round((float) $region->revenue, 2),
                ];
            })->values();

        return response()->json([
            'data' => [
                'provinces' => $provincesData,
                'regions' => $regions,
            ],
        ]);
    }
}
