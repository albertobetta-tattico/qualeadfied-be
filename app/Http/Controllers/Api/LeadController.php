<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leads = $query->with(['category', 'province', 'source'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($leads);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['category', 'province', 'source']);

        return response()->json(['lead' => $lead]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'province_id' => ['required', 'exists:provinces,id'],
            'source_id' => ['required', 'exists:lead_sources,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'request_text' => ['required', 'string'],
            'extra_tags' => ['nullable', 'array'],
            'generated_at' => ['sometimes', 'date'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['status'] = 'free';
        $validated['current_shares'] = 0;

        $lead = Lead::create($validated);

        return response()->json(['lead' => $lead], 201);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'province_id' => ['sometimes', 'exists:provinces,id'],
            'source_id' => ['sometimes', 'exists:lead_sources,id'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'request_text' => ['sometimes', 'string'],
            'extra_tags' => ['nullable', 'array'],
        ]);

        $lead->update($validated);

        return response()->json(['lead' => $lead]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        // Prevent deletion of sold leads
        if ($lead->sales()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a lead that has been sold.',
            ], 422);
        }

        $lead->delete();

        return response()->json(null, 204);
    }
}
