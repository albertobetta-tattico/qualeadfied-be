<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLead;
use App\Exports\UserLeadsExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class UserLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserLead::where('user_id', $request->user()->id);

        if ($request->filled('contact_status')) {
            $query->where('contact_status', $request->input('contact_status'));
        }

        if ($request->filled('acquisition_type')) {
            $query->where('acquisition_type', $request->input('acquisition_type'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('lead.categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        if ($request->filled('date_from')) {
            $query->where('purchased_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('purchased_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'purchased_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        $userLeads = $query->with(['lead.categories', 'lead.province'])
            ->paginate($perPage);

        return $this->paginatedResponse($userLeads);
    }

    public function show(UserLead $userLead): JsonResponse
    {
        $userLead->load(['lead.categories', 'lead.province']);

        return response()->json(['data' => $userLead]);
    }

    public function update(Request $request, UserLead $userLead): JsonResponse
    {
        $validated = $request->validate([
            'contact_status' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string'],
            'last_contacted_at' => ['nullable', 'date'],
        ]);

        $userLead->update($validated);

        return response()->json(['data' => $userLead]);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => ['required', 'in:csv,excel'],
            'category_id' => ['nullable', 'integer'],
            'contact_status' => ['nullable', 'string'],
        ]);

        $format = $request->input('format');
        $extension = $format === 'excel' ? 'xlsx' : 'csv';
        $filename = 'leads-export-' . Str::random(8) . '.' . $extension;
        $path = 'exports/' . $filename;

        $export = new UserLeadsExport(
            $request->user()->id,
            $request->input('category_id'),
            $request->input('contact_status')
        );

        if ($format === 'excel') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        }

        $url = asset('storage/' . $path);

        return response()->json(['data' => ['url' => $url]]);
    }
}
