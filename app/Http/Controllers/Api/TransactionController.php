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
        $now = \Carbon\Carbon::now();
        $succeededVolume = Transaction::where('status', 'succeeded')->sum('amount');
        $totalVolume = Transaction::sum('amount');
        $totalCount = Transaction::count();
        $succeededCount = Transaction::where('status', 'succeeded')->count();
        $failedCount = Transaction::where('status', 'failed')->count();
        $pendingCount = Transaction::whereIn('status', ['pending', 'processing', 'requires_action'])->count();

        return response()->json(['data' => [
            'total_transactions' => $totalCount,
            'total_volume' => round((float) $totalVolume, 2),
            'successful_count' => $succeededCount,
            'successful_volume' => round((float) $succeededVolume, 2),
            'failed_count' => $failedCount,
            'failed_volume' => round((float) Transaction::where('status', 'failed')->sum('amount'), 2),
            'pending_count' => $pendingCount,
            'pending_volume' => round((float) Transaction::whereIn('status', ['pending', 'processing', 'requires_action'])->sum('amount'), 2),
            'transactions_today' => Transaction::whereDate('created_at', $now->toDateString())->count(),
            'volume_today' => round((float) Transaction::whereDate('created_at', $now->toDateString())->where('status', 'succeeded')->sum('amount'), 2),
            'transactions_this_week' => Transaction::where('created_at', '>=', $now->copy()->startOfWeek())->count(),
            'volume_this_week' => round((float) Transaction::where('created_at', '>=', $now->copy()->startOfWeek())->where('status', 'succeeded')->sum('amount'), 2),
            'transactions_this_month' => Transaction::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
            'volume_this_month' => round((float) Transaction::where('created_at', '>=', $now->copy()->startOfMonth())->where('status', 'succeeded')->sum('amount'), 2),
            'success_rate' => $totalCount > 0 ? round(($succeededCount / $totalCount) * 100, 1) : 0,
            'by_payment_type' => [
                'card' => Transaction::where('payment_type', 'card')->count(),
                'bank_transfer' => Transaction::where('payment_type', 'bank_transfer')->count(),
                'sepa_debit' => Transaction::where('payment_type', 'sepa_debit')->count(),
            ],
        ]]);
    }
}
