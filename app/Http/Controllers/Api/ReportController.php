<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Sales stats: monthly revenue, order counts, avg order value.
     */
    public function sales(Request $request): JsonResponse
    {
        $months = $request->input('months', 12);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $monthlyData = Order::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as order_count,
                SUM(total) as revenue,
                AVG(total) as avg_order_value
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $totalRevenue = Order::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('total');

        $totalOrders = Order::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->count();

        return response()->json(['data' => [
            'period_start' => $startDate->toDateString(),
            'period_end' => Carbon::now()->toDateString(),
            'total_revenue' => round((float) $totalRevenue, 2),
            'total_orders' => $totalOrders,
            'avg_order_value' => $totalOrders > 0 ? round((float) $totalRevenue / $totalOrders, 2) : 0,
            'monthly' => $monthlyData,
        ]]);
    }

    /**
     * Category performance: leads/sales/revenue per category.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = Category::withCount('leads')
            ->with('currentPrice')
            ->get()
            ->map(function ($category) {
                $salesCount = LeadSale::whereHas('lead', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })->count();

                $salesRevenue = LeadSale::whereHas('lead', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })->sum('price_paid');

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'total_leads' => $category->leads_count,
                    'free_leads' => Lead::where('category_id', $category->id)
                        ->where('status', 'free')->count(),
                    'total_sales' => $salesCount,
                    'total_revenue' => round((float) $salesRevenue, 2),
                    'current_price' => $category->currentPrice,
                ];
            });

        return response()->json(['data' => $categories]);
    }

    /**
     * Geographic distribution: leads per province/region.
     */
    public function geographic(Request $request): JsonResponse
    {
        $byProvince = Province::withCount('leads')
            ->where('is_active', true)
            ->orderByDesc('leads_count')
            ->get()
            ->map(function ($province) {
                return [
                    'id' => $province->id,
                    'name' => $province->name,
                    'code' => $province->code,
                    'region' => $province->region,
                    'leads_count' => $province->leads_count,
                ];
            });

        $byRegion = DB::table('provinces')
            ->join('leads', 'provinces.id', '=', 'leads.province_id')
            ->where('provinces.is_active', true)
            ->selectRaw('provinces.region, COUNT(leads.id) as leads_count')
            ->groupBy('provinces.region')
            ->orderByDesc('leads_count')
            ->get();

        return response()->json(['data' => [
            'by_province' => $byProvince,
            'by_region' => $byRegion,
        ]]);
    }
}
