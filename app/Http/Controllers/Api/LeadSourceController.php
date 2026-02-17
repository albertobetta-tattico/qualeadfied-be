<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sources = LeadSource::paginate(15);

        return response()->json($sources);
    }

    public function show(LeadSource $leadSource): JsonResponse
    {
        return response()->json(['lead_source' => $leadSource]);
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

        return response()->json(['lead_source' => $source], 201);
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

        return response()->json(['lead_source' => $leadSource]);
    }

    public function destroy(LeadSource $leadSource): JsonResponse
    {
        $leadSource->delete();

        return response()->json(null, 204);
    }
}
