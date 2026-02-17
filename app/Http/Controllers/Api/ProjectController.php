<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::orderBy('position')->get();
    }

    public function store(Request $request)
    {
        $project = Project::create($request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:projects',
            'company' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'url' => 'nullable|url',
            'staging_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'docs_url' => 'nullable|url',
            'tech_stack' => 'nullable|array',
            'api_details' => 'nullable|array',
            'credentials' => 'nullable|array',
            'contacts' => 'nullable|array',
            'notes' => 'nullable|string',
            'quick_reference' => 'nullable|string',
            'position' => 'nullable|integer',
        ]));

        return response()->json($project, 201);
    }

    public function show($id)
    {
        return Project::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $project->update($request->all());
        return $project;
    }

    public function destroy($id)
    {
        Project::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
