<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledRoutine;
use Illuminate\Http\Request;

class ScheduledRoutineController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledRoutine::orderBy('schedule_time')->orderBy('position');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule_time' => 'required|string|max:50',
            'schedule_type' => 'required|string|in:daily,hourly,interval,manual',
            'frequency' => 'nullable|string|max:100',
            'assigned_to' => 'sometimes|string|max:50',
            'enabled' => 'sometimes|boolean',
            'category' => 'required|string|in:email,sms,orders,analysis,monitoring,other',
            'position' => 'sometimes|integer',
        ]);

        $routine = ScheduledRoutine::create($validated);

        return response()->json([
            'success' => true,
            'data' => $routine,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $routine = ScheduledRoutine::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'schedule_time' => 'sometimes|string|max:50',
            'schedule_type' => 'sometimes|string|in:daily,hourly,interval,manual',
            'frequency' => 'nullable|string|max:100',
            'assigned_to' => 'sometimes|string|max:50',
            'enabled' => 'sometimes|boolean',
            'category' => 'sometimes|string|in:email,sms,orders,analysis,monitoring,other',
            'position' => 'sometimes|integer',
        ]);

        $routine->update($validated);

        return response()->json([
            'success' => true,
            'data' => $routine,
        ]);
    }

    public function destroy($id)
    {
        $routine = ScheduledRoutine::findOrFail($id);
        $routine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Routine deleted.',
        ]);
    }
}
