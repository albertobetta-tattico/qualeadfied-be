<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with('order.user');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('stripe_payment_intent_id', 'like', '%'.$search.'%')
                  ->orWhere('stripe_charge_id', 'like', '%'.$search.'%');
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load('order.user');

        return response()->json(['data' => $transaction]);
    }

    public function stats(): JsonResponse
    {
        $totalAmount = Transaction::where('status', 'succeeded')->sum('amount');

        return response()->json(['data' => [
            'total' => Transaction::count(),
            'succeeded' => Transaction::where('status', 'succeeded')->count(),
            'pending' => Transaction::where('status', 'pending')->count(),
            'failed' => Transaction::where('status', 'failed')->count(),
            'refunded' => Transaction::where('status', 'refunded')->count(),
            'total_amount' => round((float) $totalAmount, 2),
        ]]);
    }
}
