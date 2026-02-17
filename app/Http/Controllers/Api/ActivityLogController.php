<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        return ActivityLog::orderByDesc('created_at')
            ->limit($request->get('limit', 50))
            ->get();
    }

    public function store(Request $request)
    {
        $log = ActivityLog::create($request->validate([
            'type' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'agent' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]));

        return response()->json($log, 201);
    }
}
