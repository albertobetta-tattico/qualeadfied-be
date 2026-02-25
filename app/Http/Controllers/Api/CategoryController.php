<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with('currentPrice');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('currentPrice');

        return response()->json(['data' => $category]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'max_shares' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $category = Category::create($validated);
        $category->load('currentPrice');

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'max_shares' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $category->update($validated);
        $category->load('currentPrice');

        return response()->json(['data' => $category]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete(); // Soft delete

        return response()->json(null, 204);
    }

    public function toggleActive(Category $category): JsonResponse
    {
        $category->update(['is_active' => !$category->is_active]);
        $category->load('currentPrice');

        return response()->json(['data' => $category]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'with_leads' => Category::has('leads')->count(),
        ]]);
    }
}
