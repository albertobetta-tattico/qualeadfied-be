<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClientNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = ClientNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'link' => $notification->link,
                    'created_at' => $notification->created_at->toISOString(),
                    'read' => $notification->read_at !== null,
                ];
            });

        return response()->json(['data' => $notifications]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = ClientNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read_at' => Carbon::now()]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        ClientNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
