<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Province::where('is_active', true)->orderBy('name');

        if ($request->boolean('grouped')) {
            $provinces = $query->get()->groupBy('region');

            return response()->json(['provinces' => $provinces]);
        }

        $provinces = $query->get();

        return response()->json(['provinces' => $provinces]);
    }

    public function show(Province $province): JsonResponse
    {
        return response()->json(['province' => $province]);
    }
}
