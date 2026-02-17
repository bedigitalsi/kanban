<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::query()->ordered();
        
        // Filter by board (default: tasks for backward compatibility)
        $query->byBoard($request->get('board', 'tasks'));
        
        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        // Filter by priority
        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }
        
        // Filter by assignee
        if ($request->has('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }
        
        $tasks = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:backlog,todo,in_progress,done',
            'priority' => 'nullable|in:low,medium,high',
            'assigned_to' => 'nullable|in:sandi,alex',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'position' => 'nullable|integer|min:0',
            'board' => 'nullable|in:tasks,journal'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Set position to end of current status if not provided
        if (!$request->has('position')) {
            $maxPosition = Task::where('status', $request->get('status', 'backlog'))->max('position');
            $request->merge(['position' => $maxPosition + 1]);
        }

        $task = Task::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:backlog,todo,in_progress,done',
            'priority' => 'sometimes|in:low,medium,high',
            'assigned_to' => 'nullable|in:sandi,alex',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'position' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $task->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }

    /**
     * Update multiple task positions (for drag and drop)
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.position' => 'required|integer|min:0',
            'tasks.*.status' => 'required|in:backlog,todo,in_progress,done'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->tasks as $taskData) {
            Task::where('id', $taskData['id'])->update([
                'position' => $taskData['position'],
                'status' => $taskData['status']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task positions updated successfully'
        ]);
    }
}
