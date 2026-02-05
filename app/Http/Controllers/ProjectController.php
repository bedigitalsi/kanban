<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('position')->get();
        
        return response()->json([
            'data' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,paused,completed,archived',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'url' => 'nullable|url|max:500',
            'staging_url' => 'nullable|url|max:500',
            'github_url' => 'nullable|url|max:500',
            'docs_url' => 'nullable|url|max:500',
            'tech_stack' => 'nullable|array',
            'api_details' => 'nullable|array',
            'credentials' => 'nullable|array',
            'contacts' => 'nullable|array',
            'notes' => 'nullable|string',
            'quick_reference' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $project = Project::create($validated);
        
        return response()->json([
            'data' => $project,
            'message' => 'Project created successfully',
        ], 201);
    }

    public function show(Project $project)
    {
        $project->load(['tasks' => function ($query) {
            $query->orderBy('position');
        }]);
        
        return response()->json([
            'data' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,paused,completed,archived',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'url' => 'nullable|url|max:500',
            'staging_url' => 'nullable|url|max:500',
            'github_url' => 'nullable|url|max:500',
            'docs_url' => 'nullable|url|max:500',
            'tech_stack' => 'nullable|array',
            'api_details' => 'nullable|array',
            'credentials' => 'nullable|array',
            'contacts' => 'nullable|array',
            'notes' => 'nullable|string',
            'quick_reference' => 'nullable|string',
            'position' => 'nullable|integer',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        
        $project->update($validated);
        
        return response()->json([
            'data' => $project->fresh(),
            'message' => 'Project updated successfully',
        ]);
    }

    public function destroy(Project $project)
    {
        // Unlink tasks (don't delete them)
        $project->tasks()->update(['project_id' => null]);
        
        $project->delete();
        
        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function updatePositions(Request $request)
    {
        $positions = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:projects,id',
            'positions.*.position' => 'required|integer',
        ]);

        foreach ($positions['positions'] as $item) {
            Project::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Positions updated']);
    }
}
