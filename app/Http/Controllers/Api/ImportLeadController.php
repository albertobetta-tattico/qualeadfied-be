<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportLeadController extends Controller
{
    /**
     * Import a single lead from an external source (e.g. Zapier).
     *
     * Authentication: via X-Api-Key header matching a LeadSource api_key.
     * The source is automatically resolved from the key.
     */
    public function store(Request $request): JsonResponse
    {
        // ── Auth: resolve LeadSource by API key ──────────────────────
        $apiKey = $request->header('X-Api-Key');

        if (! $apiKey) {
            return response()->json([
                'message' => 'Missing X-Api-Key header.',
            ], 401);
        }

        $source = LeadSource::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (! $source) {
            return response()->json([
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        // ── Validation ───────────────────────────────────────────────
        $validated = $request->validate([
            // Dati anagrafici (obbligatori)
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['required', 'string', 'max:50'],

            // Classificazione (obbligatori)
            'category'     => ['required', 'string', 'max:255'],  // slug o nome della categoria
            'province'     => ['required', 'string', 'max:10'],   // codice provincia (e.g. MI, RM, TO)
            'country'      => ['required', 'string', 'size:2'],   // ISO 3166-1 alpha-2 (e.g. IT)

            // Tracciamento acquisizione (opzionali)
            'medium'       => ['nullable', 'string', 'max:100'],  // mezzo (e.g. cpc, organic, email)
            'campaign'     => ['nullable', 'string', 'max:255'],  // campagna
            'request_text' => ['nullable', 'string'],             // la richiesta

            // Riferimenti esterni (opzionali)
            'external_id'  => ['nullable', 'string', 'max:255'],
            'extra_tags'   => ['nullable', 'array'],
            'generated_at' => ['nullable', 'date'],
        ]);

        // ── Resolve category by slug or name ─────────────────────────
        $category = Category::where('slug', $validated['category'])
            ->orWhere('name', $validated['category'])
            ->first();

        if (! $category) {
            return response()->json([
                'message' => 'Category not found.',
                'errors' => ['category' => ["No category matches '{$validated['category']}'"]],
            ], 422);
        }

        if (! $category->is_active) {
            return response()->json([
                'message' => 'Category is inactive.',
                'errors' => ['category' => ["Category '{$category->name}' is not active"]],
            ], 422);
        }

        // ── Resolve province by code ─────────────────────────────────
        $province = Province::where('code', strtoupper($validated['province']))->first();

        if (! $province) {
            return response()->json([
                'message' => 'Province not found.',
                'errors' => ['province' => ["No province matches code '{$validated['province']}'"]],
            ], 422);
        }

        // ── Create Lead ──────────────────────────────────────────────
        $lead = Lead::create([
            'category_id'  => $category->id,
            'province_id'  => $province->id,
            'source_id'    => $source->id,
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'],
            'country'      => strtoupper($validated['country']),
            'medium'       => $validated['medium'] ?? null,
            'campaign'     => $validated['campaign'] ?? null,
            'request_text' => $validated['request_text'] ?? null,
            'external_id'  => $validated['external_id'] ?? null,
            'extra_tags'   => $validated['extra_tags'] ?? null,
            'generated_at' => $validated['generated_at'] ?? now(),
            'status'       => 'free',
            'current_shares' => 0,
        ]);

        $lead->load(['category', 'province', 'source']);

        Log::info('Lead imported via API', [
            'lead_id' => $lead->id,
            'source' => $source->name,
            'category' => $category->name,
            'province' => $province->code,
        ]);

        return response()->json([
            'message' => 'Lead imported successfully.',
            'data' => $lead,
        ], 201);
    }
}
