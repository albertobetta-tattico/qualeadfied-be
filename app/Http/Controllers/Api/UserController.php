<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('clientProfile');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%'.$search.'%')
                  ->orWhereHas('clientProfile', function ($cp) use ($search) {
                      $cp->where('company_name', 'like', '%'.$search.'%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(User $user): JsonResponse
    {
        $user->load('clientProfile');

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
        ]);

        $user->update($validated);
        $user->load('clientProfile');

        return response()->json(['data' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }

    public function suspend(User $user): JsonResponse
    {
        $user->update(['status' => 'suspended']);
        $user->load('clientProfile');

        return response()->json(['data' => $user]);
    }

    public function activate(User $user): JsonResponse
    {
        $user->update(['status' => 'active']);
        $user->load('clientProfile');

        return response()->json(['data' => $user]);
    }

    public function resetPassword(User $user): JsonResponse
    {
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        // In production, this would send an email to the user
        return response()->json(['data' => [
            'message' => 'Password has been reset successfully.',
            'temporary_password' => $newPassword,
        ]]);
    }

    public function updateFreeTrial(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'free_trial_enabled' => ['sometimes', 'boolean'],
            'free_trial_leads_remaining' => ['sometimes', 'integer', 'min:0'],
        ]);

        $profile = $user->clientProfile;
        if (!$profile) {
            return response()->json(['message' => 'User does not have a client profile.'], 422);
        }

        $profile->update($validated);
        $user->load('clientProfile');

        return response()->json(['data' => $user]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'pending' => User::where('status', 'pending')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'with_free_trial' => User::whereHas('clientProfile', function ($q) {
                $q->where('free_trial_enabled', true);
            })->count(),
        ]]);
    }
}
