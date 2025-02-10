<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\EAVService;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{
    protected $eavService;

    public function __construct(EAVService $eavService)
    {
        $this->eavService = $eavService;
    }

    /**
     * Display a list of projects.
     */
    public function index()
    {
        return response()->json(Project::with('users')->get(), 200);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|integer',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|exists:attributes,name',
            'attributes.*.value' => 'required',
        ]);
    
        $project = Project::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);
    
        // Bulk upsert attributes
        if (!empty($validated['attributes'])) {
            $this->eavService->upsertAttributes($project->id, $validated['attributes']);
        }
    
        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load('attributeValues'),
        ], 201);
    }

    /**
     * Display a specific project.
     */
    public function show($id)
    {
        $project = Project::with('users', 'attributeValues.attribute')->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        return response()->json($project);
    }

    /**
     * Update a project.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|exists:attributes,name',
            'attributes.*.value' => 'required',
        ]);

        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->update($request->only(['name', 'status']));

        if (!empty($validated['attributes'])) {
            $this->eavService->upsertAttributes($project->id, $validated['attributes']);
        }

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()->load('attributeValues'),
        ]);
    }

    /**
     * Remove a project.
     */
    public function destroy($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}