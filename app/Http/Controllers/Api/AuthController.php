<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['sometimes', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'string', 'max:50'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'status' => 'pending',
        ]);

        // Create a ClientProfile for the new user
        ClientProfile::create([
            'user_id' => $user->id,
            'company_name' => $validated['company_name'] ?? '',
            'vat_number' => $validated['vat_number'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
            'billing_country' => 'IT',
            'free_trial_enabled' => true,
            'free_trial_leads_remaining' => (int) config('app.free_trial_leads', 3),
            'email_notifications_enabled' => true,
            'marketing_consent' => $validated['marketing_consent'] ?? false,
        ]);

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
        ], 201);
    }

    /**
     * @response array{message: string, user: User, token: string}
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($validated)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        // Load profile and alias as 'profile' for frontend compatibility
        $user->load('clientProfile');
        $userData = $user->toArray();
        $userData['profile'] = $userData['client_profile'] ?? null;
        unset($userData['client_profile']);

        return response()->json([
            'message' => 'Login successful.',
            'user' => $userData,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * @response array{user: User}
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('clientProfile');

        $userData = $user->toArray();
        $userData['profile'] = $userData['client_profile'] ?? null;
        unset($userData['client_profile']);

        return response()->json([
            'user' => $userData,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Password reset logic will be implemented with notifications
        return response()->json([
            'message' => 'If the email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Password reset logic will be implemented with tokens
        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        // Email verification will be implemented with signed URLs
        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }
}
