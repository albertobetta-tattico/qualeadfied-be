<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserLead::where('user_id', $request->user()->id);

        if ($request->filled('contact_status')) {
            $query->where('contact_status', $request->input('contact_status'));
        }

        $userLeads = $query->with('lead')
            ->orderByDesc('purchased_at')
            ->paginate(15);

        return response()->json($userLeads);
    }

    public function show(UserLead $userLead): JsonResponse
    {
        $userLead->load(['lead.category', 'lead.province']);

        return response()->json(['user_lead' => $userLead]);
    }

    public function update(Request $request, UserLead $userLead): JsonResponse
    {
        $validated = $request->validate([
            'contact_status' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string'],
            'last_contacted_at' => ['nullable', 'date'],
        ]);

        $userLead->update($validated);

        return response()->json(['user_lead' => $userLead]);
    }
}
