<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderItemController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        $items = $order->items()->with(['lead', 'package'])->get();

        return response()->json(['items' => $items]);
    }
}
