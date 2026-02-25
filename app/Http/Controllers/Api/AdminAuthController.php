<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Enums\AdminStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Admin login.
     *
     * @response array{message: string, admin: Admin, token: string}
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $validated['email'])->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($admin->status !== AdminStatus::Active) {
            return response()->json([
                'message' => 'Account is inactive.',
            ], 403);
        }

        $admin->update(['last_login_at' => now()]);

        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    /**
     * Get current admin.
     *
     * @response array{admin: Admin}
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'admin' => $request->user(),
        ]);
    }

    /**
     * Admin logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
