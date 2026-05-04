<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicCatalogController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @response array{
     *   data: list<array{
     *     id: int,
     *     category: \App\Models\Category,
     *     province: \App\Models\Province,
     *     request_preview: string,
     *     generated_at: string,
     *     status: \App\Enums\LeadStatus,
     *     current_shares: int
     *   }>,
     *   current_page: int,
     *   last_page: int,
     *   per_page: int,
     *   total: int
     * }
     */
    public function leads(Request $request): JsonResponse
    {
        $query = Lead::query()->whereIn('status', ['free', 'sold_shared']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        $availability = $request->input('availability');
        if ($availability === 'exclusive') {
            $query->where('status', 'free');
        } elseif ($availability === 'shared') {
            $query->where('status', 'sold_shared');
        }

        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('generated_at', $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $leads = $query->with([
                'categories' => fn ($q) => $q->select('categories.id', 'name', 'slug', 'max_shares')->with('currentPrice'),
                'province:id,name,code',
            ])
            ->paginate($perPage)
            ->through(function (Lead $lead) {
                $category = $lead->categories->first();
                $maxShares = (int) ($category?->max_shares ?? 3);
                $currentShares = (int) ($lead->current_shares ?? 0);
                $sharedSlotsAvailable = max(0, $maxShares - $currentShares);

                $price = $category?->currentPrice;
                $sharedPrices = array_values($price?->shared_prices ?? []);
                $sharedSlotPrice = $sharedPrices[$currentShares] ?? $sharedPrices[0] ?? null;
                $exclusivePrice = $price ? (float) $price->exclusive_price : null;

                $isExclusiveAvailable = $lead->status === LeadStatus::Free;
                $isAvailable = $isExclusiveAvailable || ($lead->status === LeadStatus::SoldShared && $sharedSlotsAvailable > 0);

                $basePrice = $sharedSlotPrice !== null ? (float) $sharedSlotPrice : $exclusivePrice;

                return [
                    'id' => $lead->id,
                    'categories' => $lead->categories,
                    'province' => $lead->province,
                    'request_preview' => Str::limit($lead->request_text, 80),
                    'generated_at' => $lead->generated_at,
                    'status' => $lead->status,
                    'current_shares' => $currentShares,
                    'max_shares' => $maxShares,
                    'shared_slots_available' => $sharedSlotsAvailable,
                    'shared_slots_total' => $maxShares,
                    'is_exclusive_available' => $isExclusiveAvailable,
                    'is_available' => $isAvailable,
                    'exclusive_price' => $exclusivePrice,
                    'shared_price' => $sharedSlotPrice !== null ? (float) $sharedSlotPrice : null,
                    'base_price' => $basePrice,
                ];
            });

        return $this->paginatedResponse($leads);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['leads' => function ($query) {
                $query->whereIn('status', ['free', 'sold_shared']);
            }])
            ->with('currentPrice')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function provinces(): JsonResponse
    {
        $provinces = Province::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'region']);

        return response()->json(['provinces' => $provinces]);
    }

    public function homepageContent(): JsonResponse
    {
        $stats = [
            'total_leads_available' => Lead::whereIn('status', ['free', 'sold_shared'])->count(),
            'categories_count' => Category::where('is_active', true)->count(),
            'provinces_covered' => Province::where('is_active', true)
                ->whereHas('leads', fn ($q) => $q->whereIn('status', ['free', 'sold_shared']))
                ->count(),
            'satisfied_clients' => 500,
        ];

        $featuredCategories = Category::where('is_active', true)
            ->withCount(['leads' => function ($query) {
                $query->whereIn('status', ['free', 'sold_shared']);
            }])
            ->with('currentPrice')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return response()->json([
            'stats' => $stats,
            'featured_categories' => $featuredCategories,
        ]);
    }
}
