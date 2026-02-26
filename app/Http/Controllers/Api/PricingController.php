<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PricingController extends Controller
{
    /**
     * List current prices (where valid_to IS NULL) with category, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CategoryPrice::with('category')
            ->whereNull('valid_to');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('category', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        $sortBy = $request->input('sort_by', 'valid_from');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    /**
     * Get current price for a specific category.
     */
    public function show(int $categoryId): JsonResponse
    {
        $category = Category::findOrFail($categoryId);
        $currentPrice = CategoryPrice::where('category_id', $categoryId)
            ->whereNull('valid_to')
            ->latest('valid_from')
            ->first();

        return response()->json(['data' => [
            'category' => $category,
            'current_price' => $currentPrice,
        ]]);
    }

    /**
     * Close old price and create new one for a category.
     */
    public function update(Request $request, int $categoryId): JsonResponse
    {
        $category = Category::findOrFail($categoryId);

        $validated = $request->validate([
            'exclusive_price' => ['required', 'numeric', 'min:0'],
            'shared_prices' => ['required', 'array'],
        ]);

        $now = Carbon::now();

        // Close the current price
        CategoryPrice::where('category_id', $categoryId)
            ->whereNull('valid_to')
            ->update(['valid_to' => $now]);

        // Create new price
        $newPrice = CategoryPrice::create([
            'category_id' => $categoryId,
            'exclusive_price' => $validated['exclusive_price'],
            'shared_prices' => $validated['shared_prices'],
            'valid_from' => $now,
            'valid_to' => null,
        ]);

        $newPrice->load('category');

        return response()->json(['data' => $newPrice]);
    }

    /**
     * List all prices ordered by created_at desc, with category name, paginated.
     */
    public function history(Request $request): JsonResponse
    {
        $query = CategoryPrice::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $query->orderByDesc('created_at');

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    /**
     * Pricing statistics.
     */
    public function stats(): JsonResponse
    {
        $totalCategories = Category::where('is_active', true)->count();
        $categoriesWithPrices = CategoryPrice::whereNull('valid_to')
            ->distinct('category_id')
            ->count('category_id');
        $avgExclusivePrice = CategoryPrice::whereNull('valid_to')->avg('exclusive_price');
        $lastPriceChange = CategoryPrice::latest('created_at')->value('created_at');

        return response()->json(['data' => [
            'total_categories' => $totalCategories,
            'categories_with_prices' => $categoriesWithPrices,
            'categories_without_prices' => $totalCategories - $categoriesWithPrices,
            'avg_exclusive_price' => round((float) ($avgExclusivePrice ?? 0), 2),
            'total_price_changes' => CategoryPrice::count(),
            'last_price_change' => $lastPriceChange,
        ]]);
    }
}
