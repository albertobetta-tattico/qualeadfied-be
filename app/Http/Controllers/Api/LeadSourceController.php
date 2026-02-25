<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeadSource::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(LeadSource $leadSource): JsonResponse
    {
        return response()->json(['data' => $leadSource]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:lead_sources,slug'],
            'description' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'unique:lead_sources,api_key'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $source = LeadSource::create($validated);

        return response()->json(['data' => $source], 201);
    }

    public function update(Request $request, LeadSource $leadSource): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:lead_sources,slug,' . $leadSource->id],
            'description' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'unique:lead_sources,api_key,' . $leadSource->id],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $leadSource->update($validated);

        return response()->json(['data' => $leadSource]);
    }

    public function destroy(LeadSource $leadSource): JsonResponse
    {
        $leadSource->delete();

        return response()->json(null, 204);
    }

    public function regenerateKey(LeadSource $leadSource): JsonResponse
    {
        $leadSource->update(['api_key' => Str::uuid()->toString()]);

        // Make api_key visible for this response since it was just regenerated
        return response()->json(['data' => $leadSource->makeVisible('api_key')]);
    }
}
