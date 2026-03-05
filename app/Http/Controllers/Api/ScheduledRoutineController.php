<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledRoutine;
use Illuminate\Http\Request;

class ScheduledRoutineController extends Controller
{
    public function index()
    {
        return ScheduledRoutine::orderBy("position")->get();
    }

    public function toggle($id)
    {
        $routine = ScheduledRoutine::findOrFail($id);
        $routine->enabled = !$routine->enabled;
        $routine->save();
        return response()->json(["success" => true, "enabled" => $routine->enabled]);
    }

    public function update(Request $request, $id)
    {
        $routine = ScheduledRoutine::findOrFail($id);
        $routine->update($request->only(["title", "description", "enabled", "assigned_to", "frequency", "schedule_time", "schedule_type", "category"]));
        return response()->json(["success" => true, "data" => $routine]);
    }
}
