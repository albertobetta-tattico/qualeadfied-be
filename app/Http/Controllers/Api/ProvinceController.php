<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Province::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('region')) {
            $query->where('region', $request->input('region'));
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Province $province): JsonResponse
    {
        return response()->json(['data' => $province]);
    }

    public function toggleActive(Province $province): JsonResponse
    {
        $province->update(['is_active' => !$province->is_active]);

        return response()->json(['data' => $province]);
    }

    public function stats(): JsonResponse
    {
        $byRegion = Province::selectRaw('region, count(*) as count')
            ->groupBy('region')
            ->orderBy('region')
            ->pluck('count', 'region');

        return response()->json(['data' => [
            'total' => Province::count(),
            'active' => Province::where('is_active', true)->count(),
            'inactive' => Province::where('is_active', false)->count(),
            'by_region' => $byRegion,
        ]]);
    }
}
