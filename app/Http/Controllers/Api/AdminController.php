<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Admin::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%'.$search.'%')
                  ->orWhere('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }

    public function show(Admin $admin): JsonResponse
    {
        return response()->json(['data' => $admin]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:admins,email'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
            'status' => ['sometimes', 'string'],
        ]);

        $admin = Admin::create($validated);

        return response()->json(['data' => $admin], 201);
    }

    public function update(Request $request, Admin $admin): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:admins,email,' . $admin->id],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
        ]);

        $admin->update($validated);

        return response()->json(['data' => $admin]);
    }

    public function destroy(Admin $admin): JsonResponse
    {
        $admin->delete();

        return response()->json(null, 204);
    }

    public function resetPassword(Admin $admin): JsonResponse
    {
        $newPassword = \Illuminate\Support\Str::random(12);
        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make($newPassword)]);

        return response()->json(['data' => [
            'message' => 'Password has been reset successfully.',
            'temporary_password' => $newPassword,
        ]]);
    }
}
