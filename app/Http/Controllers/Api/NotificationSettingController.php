<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $settings = NotificationSetting::where('user_id', $request->user()->id)
            ->with('category')
            ->get();

        return response()->json(['notification_settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.category_id' => ['required', 'exists:categories,id'],
            'settings.*.frequency' => ['required', 'string'],
            'settings.*.enabled' => ['required', 'boolean'],
        ]);

        $userId = $request->user()->id;

        foreach ($validated['settings'] as $setting) {
            NotificationSetting::updateOrCreate(
                [
                    'user_id' => $userId,
                    'category_id' => $setting['category_id'],
                ],
                [
                    'frequency' => $setting['frequency'],
                    'enabled' => $setting['enabled'],
                    'category_name' => \App\Models\Category::find($setting['category_id'])->name,
                ]
            );
        }

        $settings = NotificationSetting::where('user_id', $userId)->get();

        return response()->json(['notification_settings' => $settings]);
    }
}
