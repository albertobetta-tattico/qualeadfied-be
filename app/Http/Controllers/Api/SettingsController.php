<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get email notification configs per category.
     */
    public function notifications(): JsonResponse
    {
        $categories = Category::where('is_active', true)->get();

        $configs = $categories->map(function ($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'enabled' => true,
                'notify_on_new_lead' => true,
                'notify_on_sale' => true,
                'email_template' => 'default',
            ];
        });

        return response()->json(['data' => $configs]);
    }

    /**
     * Update notification config for a category (stub).
     */
    public function updateNotification(Request $request, int $categoryId): JsonResponse
    {
        return response()->json(['data' => array_merge(
            ['category_id' => $categoryId],
            $request->all()
        )]);
    }

    /**
     * Update SMTP config (stub).
     */
    public function updateSmtp(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'message' => 'SMTP configuration updated successfully.',
        ]]);
    }

    /**
     * Send test email (stub).
     */
    public function testEmail(Request $request): JsonResponse
    {
        $email = $request->input('email', 'test@example.com');

        return response()->json(['data' => [
            'message' => "Test email sent to {$email}.",
            'success' => true,
        ]]);
    }

    /**
     * Get Fatture in Cloud config (stub).
     */
    public function fattureCloudConfig(): JsonResponse
    {
        return response()->json(['data' => [
            'enabled' => false,
            'api_key' => null,
            'api_secret' => null,
            'company_id' => null,
            'company_name' => null,
            'auto_create_invoices' => false,
            'auto_send_sdi' => false,
            'connected_at' => null,
        ]]);
    }

    /**
     * Update Fatture in Cloud config (stub).
     */
    public function updateFattureCloudConfig(Request $request): JsonResponse
    {
        return response()->json(['data' => array_merge(
            $request->all(),
            ['connected_at' => null]
        )]);
    }

    /**
     * Test Fatture in Cloud connection (stub).
     */
    public function testFattureCloudConnection(): JsonResponse
    {
        return response()->json(['data' => [
            'success' => false,
            'error' => 'Fatture in Cloud integration not configured. Please provide API credentials.',
        ]]);
    }
}
