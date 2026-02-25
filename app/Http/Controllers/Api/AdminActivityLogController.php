<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminActivityLog::with('admin');

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->input('admin_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('entity')) {
            $query->where('entity', $request->input('entity'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        return $this->paginatedResponse($query->paginate($perPage));
    }
}
