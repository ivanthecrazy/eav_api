<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\EAVService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Services\ProjectFilterService;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    protected $eavService;
    protected $filterService;

    public function __construct(EAVService $eavService, ProjectFilterService $filterService)
    {
        $this->eavService = $eavService;
        $this->filterService = $filterService;
    }

    /**
     * Display a list of projects.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::with('users', 'attributeValues.attribute')
            ->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id());
            });

        $perPage = $request->query('per_page', 10);
        $projects = $this->filterService->applyFilters($request, $query)->paginate($perPage);

        return response()->json($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|integer',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|exists:attributes,name',
            'attributes.*.value' => 'required',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        $assignedUsers = array_unique(array_merge([$request->user()->id], $validated['user_ids'] ?? []));
        $project->users()->attach($assignedUsers);

        if (!empty($validated['attributes'])) {
            $this->eavService->upsertAttributes($project->id, $validated['attributes']);
        }

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load('users', 'attributeValues'),
        ], 201);
    }

    /**
     * Display a specific project.
     */
    public function show($id)
    {
        $project = Project::with('users', 'attributeValues.attribute')
            ->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        $this->authorize('view', $project);

        return response()->json($project);
    }

    /**
     * Update a project.
     */
    public function update(Request $request, $id)
    {
        $project = Project::whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|exists:attributes,name',
            'attributes.*.value' => 'required',
        ]);

        $project->update($request->only(['name', 'status']));

        if (!empty($validated['attributes'])) {
            $this->eavService->upsertAttributes($project->id, $validated['attributes']);
        }

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()->load('users', 'attributeValues'),
        ]);
    }

    /**
     * Remove a project.
     */
    public function destroy($id)
    {
        $project = Project::whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}