<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['sometimes', 'string', 'in:active,pending,suspended'],

            'company_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:32'],
            'contact_first_name' => ['required', 'string', 'max:100'],
            'contact_last_name' => ['required', 'string', 'max:100'],

            'free_trial_enabled' => ['sometimes', 'boolean'],
            'free_trial_leads_total' => ['sometimes', 'integer', 'min:0'],
            'notify_new_leads' => ['sometimes', 'boolean'],

            'billing_data' => ['sometimes', 'array'],
            'billing_data.address' => ['nullable', 'string', 'max:255'],
            'billing_data.city' => ['nullable', 'string', 'max:100'],
            'billing_data.province' => ['nullable', 'string', 'max:10'],
            'billing_data.postal_code' => ['nullable', 'string', 'max:10'],
            'billing_data.country' => ['nullable', 'string', 'max:2'],
            'billing_data.sdi_code' => ['nullable', 'string', 'max:20'],
            'billing_data.pec' => ['nullable', 'string', 'email', 'max:255'],

            'bank_data' => ['sometimes', 'array'],
            'bank_data.iban' => ['nullable', 'string', 'max:34'],
            'bank_data.bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_data.bic_swift' => ['nullable', 'string', 'max:11'],
            'bank_data.bank_name' => ['nullable', 'string', 'max:255'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $billing = $validated['billing_data'] ?? [];
        $bank = $validated['bank_data'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];

        $user = DB::transaction(function () use ($validated, $billing, $bank, $categoryIds) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'client',
                'status' => $validated['status'] ?? 'active',
            ]);

            $profile = $user->clientProfile()->create([
                'company_name' => $validated['company_name'],
                'vat_number' => $validated['vat_number'],
                'phone' => $validated['phone'],
                'first_name' => $validated['contact_first_name'],
                'last_name' => $validated['contact_last_name'],
                'billing_address' => $billing['address'] ?? '',
                'billing_city' => $billing['city'] ?? '',
                'billing_province' => $billing['province'] ?? '',
                'billing_zip' => $billing['postal_code'] ?? '',
                'billing_country' => $billing['country'] ?? 'IT',
                'sdi_code' => $billing['sdi_code'] ?? null,
                'pec_email' => $billing['pec'] ?? null,
                'bank_iban' => $bank['iban'] ?? null,
                'bank_account_holder' => $bank['bank_account_holder'] ?? null,
                'bank_bic_swift' => $bank['bic_swift'] ?? null,
                'bank_name' => $bank['bank_name'] ?? null,
                'free_trial_enabled' => $validated['free_trial_enabled'] ?? false,
                'free_trial_leads_remaining' => $validated['free_trial_leads_total'] ?? 0,
                'email_notifications_enabled' => $validated['notify_new_leads'] ?? true,
            ]);

            if (!empty($categoryIds)) {
                $profile->categories()->sync($categoryIds);
            }

            return $user->load(['clientProfile.categories']);
        });

        return response()->json(['data' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['clientProfile.categories']);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:active,pending,suspended'],

            'company_name' => ['sometimes', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'string', 'max:32'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'contact_first_name' => ['sometimes', 'string', 'max:100'],
            'contact_last_name' => ['sometimes', 'string', 'max:100'],

            'free_trial_enabled' => ['sometimes', 'boolean'],
            'free_trial_leads_total' => ['sometimes', 'integer', 'min:0'],
            'notify_new_leads' => ['sometimes', 'boolean'],

            'billing_data' => ['sometimes', 'array'],
            'billing_data.address' => ['nullable', 'string', 'max:255'],
            'billing_data.city' => ['nullable', 'string', 'max:100'],
            'billing_data.province' => ['nullable', 'string', 'max:10'],
            'billing_data.postal_code' => ['nullable', 'string', 'max:10'],
            'billing_data.country' => ['nullable', 'string', 'max:2'],
            'billing_data.sdi_code' => ['nullable', 'string', 'max:20'],
            'billing_data.pec' => ['nullable', 'string', 'email', 'max:255'],

            'bank_data' => ['sometimes', 'array'],
            'bank_data.iban' => ['nullable', 'string', 'max:34'],
            'bank_data.bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_data.bic_swift' => ['nullable', 'string', 'max:11'],
            'bank_data.bank_name' => ['nullable', 'string', 'max:255'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        DB::transaction(function () use ($validated, $user) {
            $userFields = array_intersect_key($validated, array_flip(['email', 'role', 'status']));
            if (!empty($userFields)) {
                $user->update($userFields);
            }

            $profile = $user->clientProfile()->firstOrNew(['user_id' => $user->id]);

            $profileMap = [
                'company_name' => $validated['company_name'] ?? null,
                'vat_number' => $validated['vat_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'first_name' => $validated['contact_first_name'] ?? null,
                'last_name' => $validated['contact_last_name'] ?? null,
                'free_trial_enabled' => $validated['free_trial_enabled'] ?? null,
                'free_trial_leads_remaining' => $validated['free_trial_leads_total'] ?? null,
                'email_notifications_enabled' => $validated['notify_new_leads'] ?? null,
            ];

            if (array_key_exists('billing_data', $validated)) {
                $b = $validated['billing_data'] ?? [];
                $profileMap['billing_address'] = $b['address'] ?? '';
                $profileMap['billing_city'] = $b['city'] ?? '';
                $profileMap['billing_province'] = $b['province'] ?? '';
                $profileMap['billing_zip'] = $b['postal_code'] ?? '';
                $profileMap['billing_country'] = $b['country'] ?? 'IT';
                $profileMap['sdi_code'] = $b['sdi_code'] ?? null;
                $profileMap['pec_email'] = $b['pec'] ?? null;
            }

            if (array_key_exists('bank_data', $validated)) {
                $bk = $validated['bank_data'] ?? [];
                $profileMap['bank_iban'] = $bk['iban'] ?? null;
                $profileMap['bank_account_holder'] = $bk['bank_account_holder'] ?? null;
                $profileMap['bank_bic_swift'] = $bk['bic_swift'] ?? null;
                $profileMap['bank_name'] = $bk['bank_name'] ?? null;
            }

            $profileMap = array_filter($profileMap, fn ($v) => $v !== null);

            if (!empty($profileMap)) {
                $profile->fill($profileMap);
                $profile->save();
            }

            if (array_key_exists('category_ids', $validated)) {
                $profile->categories()->sync($validated['category_ids']);
            }
        });

        $user->load(['clientProfile.categories']);

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
