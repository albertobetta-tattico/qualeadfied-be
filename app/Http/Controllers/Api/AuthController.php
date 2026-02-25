<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'status' => 'pending',
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

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
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
        return response()->json([
            'user' => $request->user(),
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
}
