<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()->orderBy('position');

        if ($request->has('board')) $query->board($request->board);
        if ($request->has('status')) $query->status($request->status);
        if ($request->has('assigned_to')) $query->assignedTo($request->assigned_to);

        return $query->get();
    }

    public function store(Request $request)
    {
        $task = Task::create($request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:backlog,todo,in_progress,done',
            'priority' => 'in:low,medium,high',
            'assigned_to' => 'nullable|in:sandi,alex',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'position' => 'nullable|integer',
            'project_id' => 'nullable|exists:projects,id',
            'project' => 'nullable|string',
            'board' => 'nullable|string|max:30',
        ]));

        return response()->json($task, 201);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->update($request->all());
        return $task;
    }

    public function destroy($id)
    {
        Task::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function positions(Request $request)
    {
        $request->validate(['positions' => 'required|array']);
        foreach ($request->positions as $id => $pos) {
            Task::where('id', $id)->update(['position' => $pos]);
        }
        return response()->json(['ok' => true]);
    }
}
