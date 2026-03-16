<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profiles = ClientProfile::with('user')->paginate(15);

        return $this->paginatedResponse($profiles);
    }

    public function show(Request $request, ?ClientProfile $clientProfile = null): JsonResponse
    {
        // If no profile is provided, return the authenticated user's own profile
        if ($clientProfile === null) {
            $clientProfile = ClientProfile::where('user_id', $request->user()->id)->firstOrFail();
        }

        $clientProfile->load('user');

        return response()->json(['data' => $clientProfile]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:client_profiles,user_id'],
            'company_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_province' => ['nullable', 'string', 'max:10'],
            'billing_zip' => ['nullable', 'string', 'max:10'],
            'billing_country' => ['nullable', 'string', 'max:5'],
            'sdi_code' => ['nullable', 'string', 'max:10'],
            'pec_email' => ['nullable', 'email', 'max:255'],
        ]);

        $profile = ClientProfile::create($validated);

        return response()->json(['data' => $profile], 201);
    }

    public function update(Request $request, ?ClientProfile $clientProfile = null): JsonResponse
    {
        // If no profile is provided, update the authenticated user's own profile
        if ($clientProfile === null) {
            $clientProfile = ClientProfile::where('user_id', $request->user()->id)->firstOrFail();
        }

        $validated = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_province' => ['nullable', 'string', 'max:10'],
            'billing_zip' => ['nullable', 'string', 'max:10'],
            'billing_country' => ['nullable', 'string', 'max:5'],
            'sdi_code' => ['nullable', 'string', 'max:10'],
            'pec_email' => ['nullable', 'email', 'max:255'],
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $clientProfile->update($validated);

        return response()->json(['data' => $clientProfile]);
    }

    public function updateBilling(Request $request): JsonResponse
    {
        $profile = ClientProfile::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'billing_address' => ['required', 'string', 'max:255'],
            'billing_city' => ['required', 'string', 'max:100'],
            'billing_province' => ['required', 'string', 'max:10'],
            'billing_zip' => ['required', 'string', 'max:10'],
            'billing_country' => ['required', 'string', 'max:5'],
            'sdi_code' => ['nullable', 'string', 'max:10'],
            'pec_email' => ['nullable', 'email', 'max:255'],
        ]);

        $profile->update($validated);

        return response()->json(['message' => 'Billing data updated']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $profile = ClientProfile::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $profile->update($validated);

        return response()->json(['message' => 'Preferences updated']);
    }
}
