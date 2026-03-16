<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $packages = UserPackage::where('user_id', $request->user()->id)
            ->with('package')
            ->orderByDesc('purchased_at')
            ->get();

        return response()->json(['data' => $packages]);
    }

    public function show(Request $request, UserPackage $userPackage): JsonResponse
    {
        $userPackage->load('package');

        $stats = [
            'exclusive_remaining' => $userPackage->exclusive_leads_total - $userPackage->exclusive_leads_used,
            'shared_remaining' => $userPackage->shared_leads_total - $userPackage->shared_leads_used,
            'total_remaining' => ($userPackage->exclusive_leads_total - $userPackage->exclusive_leads_used)
                + ($userPackage->shared_leads_total - $userPackage->shared_leads_used),
        ];

        return response()->json([
            'data' => array_merge($userPackage->toArray(), ['usage_stats' => $stats]),
        ]);
    }
}
