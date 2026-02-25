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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->input('source_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);

        $leads = $query->with(['category', 'province', 'source'])
            ->paginate($perPage);

        return $this->paginatedResponse($leads);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['category', 'province', 'source']);

        return response()->json(['data' => $lead]);
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
        $lead->load(['category', 'province', 'source']);

        return response()->json(['data' => $lead], 201);
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
        $lead->load(['category', 'province', 'source']);

        return response()->json(['data' => $lead]);
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

    public function stats(): JsonResponse
    {
        return response()->json(['data' => [
            'total' => Lead::count(),
            'free' => Lead::where('status', 'free')->count(),
            'sold_exclusive' => Lead::where('status', 'sold_exclusive')->count(),
            'sold_shared' => Lead::where('status', 'sold_shared')->count(),
            'exhausted' => Lead::where('status', 'exhausted')->count(),
        ]]);
    }
}
