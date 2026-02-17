<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = CartItem::where('user_id', $request->user()->id)
            ->with('lead.category')
            ->orderByDesc('added_at')
            ->get();

        return response()->json(['cart_items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'purchase_mode' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['added_at'] = now();

        $item = CartItem::create($validated);

        return response()->json(['cart_item' => $item], 201);
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        $cartItem->delete();

        return response()->json(null, 204);
    }
}
