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
            ->with(['lead.category', 'lead.province'])
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
        $item->load(['lead.category', 'lead.province']);

        return response()->json(['cart_item' => $item], 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'purchase_mode' => ['required', 'in:exclusive,shared'],
        ]);

        $cartItem->update($validated);

        return response()->json(['cart_item' => $cartItem]);
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        $cartItem->delete();

        return response()->json(null, 204);
    }

    public function removeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer'],
        ]);

        CartItem::where('user_id', $request->user()->id)
            ->whereIn('id', $request->input('item_ids'))
            ->delete();

        return response()->json(['message' => 'Items removed']);
    }

    public function clearAll(Request $request): JsonResponse
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json(null, 204);
    }
}
