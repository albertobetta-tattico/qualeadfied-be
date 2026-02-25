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
        $query = Invoice::with('order.user.clientProfile');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('sdi_status')) {
            $query->where('sdi_status', $request->input('sdi_status'));
        }

        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('date_from')) {
            $query->where('issued_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('issued_at', '<=', $request->input('date_to'));
        }

        $sortBy = $request->input('sort_by', 'issued_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['order.user.clientProfile', 'items']);

        return response()->json(['data' => $invoice]);
    }

    public function stats(): JsonResponse
    {
        $totalAmount = Invoice::sum('total');

        return response()->json(['data' => [
            'total' => Invoice::count(),
            'total_amount' => round((float) $totalAmount, 2),
            'by_type' => [
                'invoice' => Invoice::where('type', 'invoice')->count(),
                'credit_note' => Invoice::where('type', 'credit_note')->count(),
            ],
            'by_sdi_status' => [
                'pending' => Invoice::where('sdi_status', 'pending')->count(),
                'sent' => Invoice::where('sdi_status', 'sent')->count(),
                'delivered' => Invoice::where('sdi_status', 'delivered')->count(),
                'rejected' => Invoice::where('sdi_status', 'rejected')->count(),
            ],
        ]]);
    }

    public function resendSdi(Invoice $invoice): JsonResponse
    {
        // Stub: In production, this would re-send the invoice to SDI
        return response()->json(['data' => [
            'message' => 'Invoice SDI re-send has been queued.',
            'invoice_id' => $invoice->id,
        ]]);
    }

    public function downloadPdf(Invoice $invoice): JsonResponse
    {
        // Stub: In production, this would generate and return a PDF
        return response()->json(['data' => [
            'message' => 'PDF generation has been queued.',
            'invoice_id' => $invoice->id,
        ]]);
    }

    public function sendEmail(Invoice $invoice): JsonResponse
    {
        // Stub: In production, this would send the invoice via email
        return response()->json(['data' => [
            'message' => 'Invoice email has been queued.',
            'invoice_id' => $invoice->id,
        ]]);
    }

    public function createCreditNote(Invoice $invoice): JsonResponse
    {
        // Stub: In production, this would create a credit note for the invoice
        return response()->json(['data' => [
            'message' => 'Credit note creation has been queued.',
            'invoice_id' => $invoice->id,
        ]]);
    }
}
