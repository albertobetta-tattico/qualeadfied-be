<?php

namespace App\Http\Controllers\Api;

use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderStatus;
use App\Enums\PackageStatus;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\UserLead;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $query->with(['user.clientProfile', 'items'])->withCount('items');

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type') || $request->filled('order_type')) {
            $type = $request->input('type') ?? $request->input('order_type');
            $query->where('type', $type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // whereDate keeps the range inclusive on the end day. Otherwise
        // `<= '2026-05-07'` is parsed as `<= '2026-05-07 00:00:00'` and
        // silently drops every order from May 7.
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        $paginator = $query->paginate($perPage);

        // Flatten client info on each row (frontend Order type expects `client`).
        $paginator->getCollection()->transform(function (Order $order) {
            $profile = $order->user?->clientProfile;
            $arr = $order->toArray();
            $arr['client'] = $profile ? [
                'id' => $order->user_id,
                'company_name' => $profile->company_name ?? '',
                'vat_number' => $profile->vat_number ?? '',
                'email' => $order->user?->email ?? '',
                'phone' => $profile->phone ?? '',
                'contact_first_name' => $profile->first_name ?? '',
                'contact_last_name' => $profile->last_name ?? '',
            ] : null;
            return $arr;
        });

        return $this->paginatedResponse($paginator);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $order->load(['user.clientProfile', 'items.lead.categories', 'items.lead.province', 'invoice', 'transactions']);

        $data = $order->toArray();

        // Flattened `client` block expected by the frontend (admin order detail
        // tab "Cliente"). The backend internally has user.clientProfile, the FE
        // type expects a single `client` object — without this mapping the tab
        // shows "Dati cliente non disponibili" because order.client is missing.
        $profile = $order->user?->clientProfile;
        $data['client'] = $profile ? [
            'id' => $order->user_id,
            'company_name' => $profile->company_name ?? '',
            'vat_number' => $profile->vat_number ?? '',
            'email' => $order->user?->email ?? '',
            'phone' => $profile->phone ?? '',
            'contact_first_name' => $profile->first_name ?? '',
            'contact_last_name' => $profile->last_name ?? '',
        ] : null;

        // Add billing_data from snapshot or profile
        $data['billing_data'] = $order->billing_snapshot ?? [
            'company_name' => $profile?->company_name ?? '',
            'vat_number' => $profile?->vat_number ?? '',
            'address' => $profile?->billing_address ?? '',
            'city' => $profile?->billing_city ?? '',
            'province' => $profile?->billing_province ?? '',
            'zip' => $profile?->billing_zip ?? '',
            'country' => $profile?->billing_country ?? 'IT',
            'sdi_code' => $profile?->sdi_code,
            'pec_email' => $profile?->pec_email,
        ];

        return response()->json(['data' => $data]);
    }

    /**
     * Stream a CSV export of orders that match the same filters as `index`.
     * Excel opens .csv natively (UTF-8 BOM ensures it preserves accents).
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Order::query()->with(['user.clientProfile']);

        $user = $request->user();
        if ($user && $user->role?->value === 'client') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->input('search').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type') || $request->filled('order_type')) {
            $type = $request->input('type') ?? $request->input('order_type');
            $query->where('type', $type);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        $query->orderByDesc('created_at');

        $filename = 'ordini-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel recognises encoding
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Numero ordine', 'Tipo', 'Stato', 'Metodo pagamento',
                'Cliente', 'P.IVA', 'Email',
                'Subtotale', 'IVA', 'Totale',
                'Pagato il', 'Creato il',
            ], ';');

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $order) {
                    $profile = $order->user?->clientProfile;
                    fputcsv($out, [
                        $order->order_number,
                        $order->type?->value ?? '',
                        $order->status?->value ?? '',
                        $order->payment_method?->value ?? '',
                        trim(($profile?->company_name ?? '') ?: trim(($profile?->first_name ?? '') . ' ' . ($profile?->last_name ?? ''))),
                        $profile?->vat_number ?? '',
                        $order->user?->email ?? '',
                        number_format((float) $order->subtotal, 2, ',', ''),
                        number_format((float) $order->vat_amount, 2, ',', ''),
                        number_format((float) $order->total, 2, ',', ''),
                        $order->paid_at?->format('Y-m-d H:i:s') ?? '',
                        $order->created_at?->format('Y-m-d H:i:s') ?? '',
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Mark a pending order as paid manually (admin only).
     *
     * Used for bonifico bancario (SEPA) orders where Stripe doesn't auto-confirm
     * — the admin verifies the wire on the bank statement and clicks "Conferma
     * pagamento" in the order detail page. Mirrors the post-payment fulfilment
     * logic of CheckoutController::confirm and ClientPackageController::
     * confirmPurchase, minus the Stripe round-trip.
     */
    public function confirmManually(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (($user->role?->value ?? null) !== 'admin' && ($user->role?->value ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== OrderStatus::Pending) {
            return response()->json([
                'message' => 'Solo gli ordini in attesa possono essere confermati manualmente.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => Carbon::now(),
            ]);

            Transaction::create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => null,
                'stripe_charge_id' => null,
                'payment_type' => TransactionPaymentType::SepaDebit,
                'amount' => $order->total,
                'currency' => 'eur',
                'status' => TransactionStatus::Succeeded,
                'processed_at' => Carbon::now(),
            ]);

            $orderItems = $order->items()->with('lead')->get();
            $freeTrialCount = 0;

            foreach ($orderItems as $item) {
                if ($item->lead_id) {
                    $acquisitionType = $item->is_free_trial
                        ? AcquisitionType::FreeTrial
                        : ($item->acquisition_mode?->value === 'exclusive'
                            ? AcquisitionType::Exclusive
                            : AcquisitionType::Shared);

                    if ($item->is_free_trial) {
                        $freeTrialCount++;
                    }

                    UserLead::firstOrCreate(
                        ['user_id' => $order->user_id, 'lead_id' => $item->lead_id],
                        [
                            'order_id' => $order->id,
                            'acquisition_type' => $acquisitionType,
                            'purchase_price' => $item->unit_price,
                            'contact_status' => ContactStatus::New,
                            'purchased_at' => Carbon::now(),
                        ]
                    );
                }

                if ($item->package_id) {
                    $package = $item->package()->first();
                    if ($package) {
                        UserPackage::firstOrCreate(
                            ['user_id' => $order->user_id, 'order_id' => $order->id, 'package_id' => $package->id],
                            [
                                'package_name' => $package->name,
                                'category_id' => $package->category_ids[0] ?? null,
                                'total_leads' => $package->exclusive_lead_quantity + $package->shared_lead_quantity,
                                'exclusive_leads_total' => $package->exclusive_lead_quantity,
                                'exclusive_leads_used' => 0,
                                'shared_leads_total' => $package->shared_lead_quantity,
                                'shared_leads_used' => 0,
                                'status' => PackageStatus::Active,
                                'purchased_at' => Carbon::now(),
                                'expires_at' => Carbon::now()->addDays(30),
                            ]
                        );
                    }
                }
            }

            if ($freeTrialCount > 0 && $order->user?->clientProfile) {
                $order->user->clientProfile->decrement('free_trial_leads_remaining', $freeTrialCount);
            }

            CartItem::where('user_id', $order->user_id)->delete();
        });

        return response()->json(['data' => ['order_id' => $order->id, 'status' => 'paid']]);
    }

    public function invoice(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Ensure client can only access their own orders
        if ($user->role?->value === 'client' && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$order->invoice_url) {
            $invoice = $order->invoice;
            if (!$invoice) {
                return response()->json(['message' => 'Invoice not available'], 404);
            }
        }

        return response()->json(['data' => [
            'url' => $order->invoice_url ?? $order->invoice?->url ?? null,
        ]]);
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
        $now = \Carbon\Carbon::now();
        $totalRevenue = Order::whereIn('status', ['paid', 'completed'])->sum('total');

        return response()->json(['data' => [
            'total_orders' => Order::count(),
            'total_revenue' => round((float) $totalRevenue, 2),
            'orders_today' => Order::whereDate('created_at', $now->toDateString())->count(),
            'revenue_today' => round((float) Order::whereDate('created_at', $now->toDateString())->whereIn('status', ['paid', 'completed'])->sum('total'), 2),
            'orders_this_week' => Order::where('created_at', '>=', $now->copy()->startOfWeek())->count(),
            'revenue_this_week' => round((float) Order::where('created_at', '>=', $now->copy()->startOfWeek())->whereIn('status', ['paid', 'completed'])->sum('total'), 2),
            'orders_this_month' => Order::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
            'revenue_this_month' => round((float) Order::where('created_at', '>=', $now->copy()->startOfMonth())->whereIn('status', ['paid', 'completed'])->sum('total'), 2),
            'orders_by_status' => [
                'pending' => Order::where('status', 'pending')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'paid' => Order::where('status', 'paid')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'failed' => Order::where('status', 'failed')->count(),
                'refunded' => Order::where('status', 'refunded')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],
            'orders_by_type' => [
                'single' => Order::where('type', 'single')->count(),
                'package' => Order::where('type', 'package')->count(),
                'free_trial' => Order::where('type', 'free_trial')->count(),
            ],
            'avg_order_value' => round((float) (Order::whereIn('status', ['paid', 'completed'])->avg('total') ?? 0), 2),
        ]]);
    }
}
