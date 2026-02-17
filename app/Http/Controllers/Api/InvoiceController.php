<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query();

        if ($request->filled('sdi_status')) {
            $query->where('sdi_status', $request->input('sdi_status'));
        }

        $invoices = $query->with('order')
            ->orderByDesc('issued_at')
            ->paginate(15);

        return response()->json($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['order', 'items']);

        return response()->json(['invoice' => $invoice]);
    }
}
