<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TaskboardController extends Controller
{
    public function index(Request $request)
    {
        $board = $request->get('board', 'tasks');
        $tasks = Task::where('board', $board)->ordered()->get()->groupBy('status');
        
        // Ensure all columns have arrays even if empty
        $tasksByStatus = [
            'backlog' => $tasks->get('backlog', collect())->values(),
            'todo' => $tasks->get('todo', collect())->values(),
            'in_progress' => $tasks->get('in_progress', collect())->values(),
            'done' => $tasks->get('done', collect())->values(),
        ];
        
        return view('taskboard', compact('tasksByStatus', 'board'));
    }

    /**
     * Store a new task via AJAX
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'status' => 'required|in:backlog,todo,in_progress,done',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'assigned_to' => 'nullable|in:sandi,alex',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'board' => 'nullable|in:tasks,journal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the next position for this status within this board
        $board = $request->get('board', 'tasks');
        $maxPosition = Task::where('status', $request->status)->where('board', $board)->max('position') ?? 0;

        $task = Task::create(array_merge($request->all(), [
            'position' => $maxPosition + 1,
            'board' => $board,
        ]));

        return response()->json([
            'success' => true,
            'task' => $task
        ]);
    }

    /**
     * Update task via AJAX
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'assigned_to' => 'nullable|in:sandi,alex',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $task->update($request->all());

        return response()->json([
            'success' => true,
            'task' => $task->fresh()
        ]);
    }

    /**
     * Update task positions for drag & drop
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $tasks = $request->input('tasks', []);

        foreach ($tasks as $taskData) {
            Task::where('id', $taskData['id'])->update([
                'status' => $taskData['status'],
                'position' => $taskData['position']
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete task
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['success' => true]);
    }
}
