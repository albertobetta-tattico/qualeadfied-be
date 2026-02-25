<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sales = LeadSale::with(['lead', 'user'])
            ->orderByDesc('sold_at')
            ->paginate(15);

        return $this->paginatedResponse($sales);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'user_id' => ['required', 'exists:users,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'user_package_id' => ['nullable', 'exists:user_packages,id'],
            'mode' => ['required', 'string'],
            'share_slot' => ['nullable', 'integer', 'min:1'],
            'price_paid' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['sold_at'] = now();

        $sale = LeadSale::create($validated);

        return response()->json(['lead_sale' => $sale], 201);
    }
}
