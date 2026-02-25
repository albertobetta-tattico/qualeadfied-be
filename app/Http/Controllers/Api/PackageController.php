<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Package::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');
            $query->whereJsonContains('category_ids', (int) $categoryId);
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

    public function show(Package $package): JsonResponse
    {
        return response()->json(['data' => $package]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'exclusive_lead_quantity' => ['required', 'integer', 'min:0'],
            'exclusive_price' => ['required', 'numeric', 'min:0'],
            'shared_lead_quantity' => ['required', 'integer', 'min:0'],
            'shared_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $package = Package::create($validated);

        return response()->json(['data' => $package], 201);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'exclusive_lead_quantity' => ['sometimes', 'integer', 'min:0'],
            'exclusive_price' => ['sometimes', 'numeric', 'min:0'],
            'shared_lead_quantity' => ['sometimes', 'integer', 'min:0'],
            'shared_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $package->update($validated);

        return response()->json(['data' => $package]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();

        return response()->json(null, 204);
    }

    public function toggleActive(Package $package): JsonResponse
    {
        $package->update(['is_active' => !$package->is_active]);

        return response()->json(['data' => $package]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => [
            'total' => Package::count(),
            'active' => Package::where('is_active', true)->count(),
            'inactive' => Package::where('is_active', false)->count(),
        ]]);
    }
}
