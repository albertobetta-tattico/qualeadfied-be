<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        // Return all settings as a flat key-value object
        $settings = SystemSetting::all()->pluck('value', 'key');
        return response()->json(['data' => $settings]);
    }

    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();
        return response()->json(['data' => $setting]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required'],
        ]);

        $setting = SystemSetting::where('key', $key)->firstOrFail();
        $setting->update(['value' => $validated['value']]);
        return response()->json(['data' => $setting]);
    }

    /**
     * Bulk update all system settings.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $settings = $request->all();

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }

        $allSettings = SystemSetting::all()->pluck('value', 'key');
        return response()->json(['data' => $allSettings]);
    }
}
