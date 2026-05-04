<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatus;
use App\Enums\PurchaseMode;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = CartItem::where('user_id', $request->user()->id)
            ->with(['lead.categories', 'lead.province'])
            ->orderByDesc('added_at')
            ->get();

        return response()->json(['cart_items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'purchase_mode' => ['required', 'in:exclusive,shared'],
        ]);

        $user = $request->user();
        $lead = Lead::with('categories.currentPrice')->findOrFail($validated['lead_id']);

        $resolved = $this->resolvePricing($user, $lead, $validated['purchase_mode']);

        $item = CartItem::create([
            'user_id' => $user->id,
            'lead_id' => $lead->id,
            'purchase_mode' => $resolved['purchase_mode'],
            'price' => $resolved['price'],
            'is_free_trial' => $resolved['is_free_trial'],
            'added_at' => now(),
        ]);

        $item->load(['lead.categories', 'lead.province']);

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

        $lead = Lead::with('categories.currentPrice')->findOrFail($cartItem->lead_id);
        $resolved = $this->resolvePricing(
            $request->user(),
            $lead,
            $validated['purchase_mode'],
            ignoreCartItemId: $cartItem->id,
        );

        $cartItem->update([
            'purchase_mode' => $resolved['purchase_mode'],
            'price' => $resolved['price'],
            'is_free_trial' => $resolved['is_free_trial'],
        ]);

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

    /**
     * Resolve price + free-trial flag server-side. The client may NOT influence the price.
     *
     * @return array{purchase_mode: PurchaseMode, price: float, is_free_trial: bool}
     */
    private function resolvePricing(
        $user,
        Lead $lead,
        string $requestedMode,
        ?int $ignoreCartItemId = null,
    ): array {
        $category = $lead->categories->first();
        if (!$category) {
            throw ValidationException::withMessages([
                'lead_id' => 'Lead non associato a una categoria.',
            ]);
        }

        $price = $category->currentPrice;
        if (!$price) {
            throw ValidationException::withMessages([
                'lead_id' => 'Listino prezzi non configurato per questa categoria.',
            ]);
        }

        if ($requestedMode === 'exclusive') {
            if ($lead->status !== LeadStatus::Free) {
                throw ValidationException::withMessages([
                    'purchase_mode' => 'Lead non disponibile in modalità esclusiva.',
                ]);
            }
            $unitPrice = (float) $price->exclusive_price;
        } else {
            $maxShares = (int) ($category->max_shares ?? 3);
            $currentShares = (int) ($lead->current_shares ?? 0);
            if ($currentShares >= $maxShares) {
                throw ValidationException::withMessages([
                    'purchase_mode' => 'Tutti gli slot condivisi sono già occupati.',
                ]);
            }
            $sharedPrices = array_values($price->shared_prices ?? []);
            $slotPrice = $sharedPrices[$currentShares] ?? $sharedPrices[0] ?? null;
            if ($slotPrice === null) {
                throw ValidationException::withMessages([
                    'purchase_mode' => 'Listino condiviso non configurato per questa categoria.',
                ]);
            }
            $unitPrice = (float) $slotPrice;
        }

        $profile = $user->clientProfile;
        $isFreeTrial = false;

        if (
            $requestedMode === 'shared'
            && $profile
            && $profile->free_trial_enabled
            && (int) $profile->free_trial_leads_remaining > 0
        ) {
            $alreadyFreeInCart = CartItem::where('user_id', $user->id)
                ->where('is_free_trial', true)
                ->when($ignoreCartItemId, fn ($q) => $q->where('id', '!=', $ignoreCartItemId))
                ->count();

            if ($alreadyFreeInCart < (int) $profile->free_trial_leads_remaining) {
                $isFreeTrial = true;
                $unitPrice = 0.0;
            }
        }

        return [
            'purchase_mode' => $isFreeTrial ? PurchaseMode::Free : PurchaseMode::from($requestedMode),
            'price' => $unitPrice,
            'is_free_trial' => $isFreeTrial,
        ];
    }
}
