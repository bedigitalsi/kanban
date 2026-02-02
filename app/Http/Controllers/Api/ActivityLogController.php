<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ActivityLogController extends Controller
{
    /**
     * List activity logs with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $logs = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Store a new activity log entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:email,sms,order_fix,analysis,integration,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $log = ActivityLog::create($request->only(['type', 'title', 'description', 'metadata']));

        return response()->json([
            'success' => true,
            'message' => 'Activity log created successfully',
            'data' => $log,
        ], 201);
    }
}
