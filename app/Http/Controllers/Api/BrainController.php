<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrainEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BrainEntry::query()->where('archived', false)->orderByDesc('pinned')->orderByDesc('updated_at');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        $entry = BrainEntry::create($validated);

        return response()->json(['success' => true, 'data' => $entry], 201);
    }

    public function update(Request $request, BrainEntry $brain): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'pinned' => 'sometimes|boolean',
            'archived' => 'sometimes|boolean',
        ]);

        $brain->update($validated);

        return response()->json(['success' => true, 'data' => $brain]);
    }

    public function destroy(BrainEntry $brain): JsonResponse
    {
        $brain->delete();

        return response()->json(['success' => true]);
    }
}
