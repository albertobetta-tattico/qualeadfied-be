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
                $q->where('full_name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->input('source_id'));
        }

        // Single combined free-text filter (sorgente / mezzo / campagna).
        // Replaces the old "Fonte" dropdown that only listed Zapier.
        if ($request->filled('source_text')) {
            $needle = trim((string) $request->input('source_text'));
            if ($needle !== '') {
                $like = '%' . $needle . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('medium', 'like', $like)
                      ->orWhere('campaign', 'like', $like)
                      ->orWhere('origin', 'like', $like)
                      ->orWhereHas('source', function ($s) use ($like) {
                          $s->where('name', 'like', $like);
                      });
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Frontend (admin/leads page) sends `generated_from` / `generated_to`,
        // we also accept `date_from` / `date_to` for backward compatibility.
        // Use whereDate to keep the range INCLUSIVE on the end day (otherwise
        // `<= '2026-05-07'` is parsed as `<= '2026-05-07 00:00:00'` and silently
        // drops every lead generated on May 7).
        $generatedFrom = $request->input('generated_from') ?? $request->input('date_from');
        if (!empty($generatedFrom)) {
            $query->whereDate('generated_at', '>=', $generatedFrom);
        }
        $generatedTo = $request->input('generated_to') ?? $request->input('date_to');
        if (!empty($generatedTo)) {
            $query->whereDate('generated_at', '<=', $generatedTo);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }

        if ($request->filled('medium')) {
            $query->where('medium', 'like', '%'.$request->input('medium').'%');
        }

        if ($request->filled('campaign')) {
            $query->where('campaign', 'like', '%'.$request->input('campaign').'%');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);

        $leads = $query->with(['categories', 'province', 'source'])
            ->paginate($perPage);

        return $this->paginatedResponse($leads);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['categories', 'province', 'source']);

        return response()->json(['data' => $lead]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'source_id' => ['required', 'exists:lead_sources,id'],
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'request_text' => ['required', 'string'],
            'extra_tags' => ['nullable', 'array'],
            'generated_at' => ['sometimes', 'date'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'medium' => ['nullable', 'string', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'size:2'],
        ]);

        $categoryIds = $validated['category_ids'];
        unset($validated['category_ids']);

        $validated['status'] = 'free';
        $validated['current_shares'] = 0;

        $lead = Lead::create($validated);
        $lead->categories()->sync($categoryIds);
        $lead->load(['categories', 'province', 'source']);

        return response()->json(['data' => $lead], 201);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'category_ids' => ['sometimes', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'source_id' => ['sometimes', 'exists:lead_sources,id'],
            'full_name' => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'request_text' => ['sometimes', 'required', 'string'],
            'extra_tags' => ['nullable', 'array'],
            'medium' => ['nullable', 'string', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'size:2'],
        ]);

        if (isset($validated['category_ids'])) {
            $lead->categories()->sync($validated['category_ids']);
            unset($validated['category_ids']);
        }

        $lead->update($validated);
        $lead->load(['categories', 'province', 'source']);

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
