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
        $settings = SystemSetting::all();

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
}
