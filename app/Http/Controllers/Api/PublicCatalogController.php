<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicCatalogController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @response array{
     *   data: list<array{
     *     id: int,
     *     category: \App\Models\Category,
     *     province: \App\Models\Province,
     *     request_preview: string,
     *     generated_at: string,
     *     status: \App\Enums\LeadStatus,
     *     current_shares: int
     *   }>,
     *   current_page: int,
     *   last_page: int,
     *   per_page: int,
     *   total: int
     * }
     */
    public function leads(Request $request): JsonResponse
    {
        $query = Lead::where('status', 'free')
            ->orWhere('status', 'sold_shared');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        $leads = $query->with(['category:id,name,slug', 'province:id,name,code'])
            ->orderByDesc('generated_at')
            ->paginate(15)
            ->through(function (Lead $lead) {
                return [
                    'id' => $lead->id,
                    'category' => $lead->category,
                    'province' => $lead->province,
                    'request_preview' => Str::limit($lead->request_text, 80),
                    'generated_at' => $lead->generated_at,
                    'status' => $lead->status,
                    'current_shares' => $lead->current_shares,
                ];
            });

        return response()->json($leads);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['leads' => function ($query) {
                $query->whereIn('status', ['free', 'sold_shared']);
            }])
            ->with('currentPrice')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function provinces(): JsonResponse
    {
        $provinces = Province::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'region']);

        return response()->json(['provinces' => $provinces]);
    }
}
