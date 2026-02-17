<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryPriceController extends Controller
{
    public function index(Category $category): JsonResponse
    {
        $prices = $category->prices()
            ->orderByDesc('valid_from')
            ->get();

        return response()->json(['prices' => $prices]);
    }

    public function store(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'exclusive_price' => ['required', 'numeric', 'min:0'],
            'shared_prices' => ['required', 'array'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
        ]);

        // Close the current active price if exists
        $category->prices()
            ->whereNull('valid_to')
            ->update(['valid_to' => $validated['valid_from']]);

        $validated['category_id'] = $category->id;
        $price = CategoryPrice::create($validated);

        return response()->json(['price' => $price], 201);
    }
}
