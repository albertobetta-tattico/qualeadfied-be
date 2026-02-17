<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLeadSubmissionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_slug' => ['required', 'string', 'exists:categories,slug'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'request_text' => ['required', 'string'],
            'extra_tags' => ['nullable', 'array'],
        ]);

        $category = Category::where('slug', $validated['category_slug'])->firstOrFail();

        $lead = Lead::create([
            'category_id' => $category->id,
            'province_id' => $validated['province_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'request_text' => $validated['request_text'],
            'extra_tags' => $validated['extra_tags'] ?? null,
            'status' => 'free',
            'current_shares' => 0,
            'generated_at' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Lead submitted successfully.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
