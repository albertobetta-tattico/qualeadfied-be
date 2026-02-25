<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query();

        // If the user is a client, scope to their own orders
        $user = $request->user();
        if ($user && $user->role?->value === 'client') {
            $query->where('user_id', $user->id);
        }

        $query->with(['user.clientProfile', 'items']);

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
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
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user.clientProfile', 'items', 'invoice', 'transactions']);

        return response()->json(['data' => $order]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.lead_id' => ['nullable', 'exists:leads,id'],
            'items.*.package_id' => ['nullable', 'exists:packages,id'],
            'items.*.acquisition_mode' => ['nullable', 'string'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . date('Y') . '-' . str_pad((string) (Order::count() + 1), 5, '0', STR_PAD_LEFT),
            'type' => $validated['type'],
            'payment_method' => $validated['payment_method'],
            'subtotal' => 0,
            'vat_rate' => 22.00,
            'vat_amount' => 0,
            'total' => 0,
            'status' => 'pending',
        ]);

        $subtotal = 0;

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $lineTotal;

            $order->items()->create([
                'lead_id' => $item['lead_id'] ?? null,
                'package_id' => $item['package_id'] ?? null,
                'acquisition_mode' => $item['acquisition_mode'] ?? null,
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
            ]);
        }

        $vatAmount = round($subtotal * 0.22, 2);
        $order->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total' => $subtotal + $vatAmount,
        ]);

        $order->load(['user.clientProfile', 'items']);

        return response()->json(['data' => $order], 201);
    }

    public function stats(): JsonResponse
    {
        $totalRevenue = Order::where('status', 'paid')->sum('total');
        $totalOrders = Order::count();

        return response()->json(['data' => [
            'total' => $totalOrders,
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'paid' => Order::where('status', 'paid')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'failed' => Order::where('status', 'failed')->count(),
            'refunded' => Order::where('status', 'refunded')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => round((float) $totalRevenue, 2),
        ]]);
    }
}
