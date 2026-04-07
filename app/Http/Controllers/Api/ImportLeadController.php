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
use Illuminate\Support\Str;

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
            // Dati anagrafici
            'full_name'    => ['required', 'string', 'max:200'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['required', 'string', 'max:50'],

            // Indirizzo (testo libero, per dati Meta non strutturati)
            'address'      => ['nullable', 'string', 'max:500'],

            // Classificazione
            'category'     => ['required', 'string', 'max:255'],  // slug o nome della categoria
            'province'     => ['nullable', 'string', 'max:10'],   // codice provincia sigla (es. MI, RM)
            'country'      => ['required', 'string', 'size:2'],   // ISO 3166-1 alpha-2 (e.g. IT)

            // Tracciamento acquisizione (opzionali)
            'medium'       => ['nullable', 'string', 'max:100'],
            'campaign'     => ['nullable', 'string', 'max:255'],
            'origin'       => ['nullable', 'string', 'max:50'],
            'request_text' => ['required', 'string'],

            // Riferimenti esterni (opzionali)
            'external_id'  => ['nullable', 'string', 'max:255'],
            'extra_tags'   => ['nullable', 'array'],
            'generated_at' => ['nullable', 'date'],
        ]);

        // ── Resolve or auto-create category ──────────────────────────
        $category = Category::where('slug', $validated['category'])
            ->orWhere('name', $validated['category'])
            ->first();

        if (! $category) {
            $slug = Str::slug($validated['category']);
            $name = ucwords(str_replace(['-', '_'], ' ', $validated['category']));

            // Secondo tentativo per slug normalizzato (evita duplicati)
            $category = Category::where('slug', $slug)->first();

            if (! $category) {
                $category = Category::create([
                    'name'       => $name,
                    'slug'       => $slug,
                    'is_active'  => true,
                    'max_shares' => 3,
                ]);

                Log::info('Category auto-created via API import', [
                    'category_id' => $category->id,
                    'name'        => $name,
                    'slug'        => $slug,
                ]);
            }
        }

        if (! $category->is_active) {
            return response()->json([
                'message' => 'Category is inactive.',
                'errors'  => ['category' => ["Category '{$category->name}' is not active"]],
            ], 422);
        }

        // ── Resolve province by code (facoltativa) ─────────────────
        $provinceId = null;

        if (! empty($validated['province'])) {
            $province = Province::where('code', strtoupper($validated['province']))->first();

            if ($province) {
                $provinceId = $province->id;
            } else {
                Log::warning('Province not found during import, skipping', [
                    'province_code' => $validated['province'],
                    'source' => $source->name,
                ]);
            }
        }

        // ── Create Lead ──────────────────────────────────────────────
        $lead = Lead::create([
            'category_id'    => $category->id,
            'province_id'    => $provinceId,
            'source_id'      => $source->id,
            'full_name'      => $validated['full_name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'],
            'address'        => $validated['address'] ?? null,
            'country'        => strtoupper($validated['country']),
            'medium'         => $validated['medium'] ?? null,
            'campaign'       => $validated['campaign'] ?? null,
            'origin'         => $validated['origin'] ?? null,
            'request_text'   => $validated['request_text'] ?? null,
            'external_id'    => $validated['external_id'] ?? null,
            'extra_tags'     => $validated['extra_tags'] ?? null,
            'generated_at'   => $validated['generated_at'] ?? now(),
            'status'         => 'free',
            'current_shares' => 0,
        ]);

        $lead->load(['category', 'province', 'source']);

        Log::info('Lead imported via API', [
            'lead_id' => $lead->id,
            'source' => $source->name,
            'category' => $category->name,
            'province' => $provinceId ? ($province->code ?? null) : null,
        ]);

        return response()->json([
            'message' => 'Lead imported successfully.',
            'data' => $lead,
        ], 201);
    }
}
