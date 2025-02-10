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
     * Display a list of projects. Available for all users, but only returns projects assigned to the authenticated user.
     * 
     * @group Projects
     * @queryParam per_page int The number of projects to return per page.
     * @queryParam filter[attribute_name_operation] string The name of the attribute to filter by. Please note that operation is optional and it can be lt, gt, lte, gte, eq, like. For like use * for wildcards.
     * @authenticated
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Project 1",
     *       "status": 1,
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00",
     *       "users": [
     *         {
     *           "id": 1,
     *           "first_name": "John",
    *           "last_name": "Doe",
     *           "email": "user1@example.com",
     *           "created_at": "2025-01-01 12:00:00",
     *           "updated_at": "2025-01-01 12:00:00"
     *         }
     *       ],
     *       "attribute_values": [
     *         {
     *           "id": 1,
     *           "attribute_id": 1,
     *           "entity_id": 1,
     *           "value": "Value 1",
     *           "created_at": "2025-01-01 12:00:00",
     *           "updated_at": "2025-01-01 12:00:00",
     *           "attribute_name": "department",
     *           "attribute": {
     *             "id": 1,
     *             "name": "Attribute 1",
     *             "type": "text"
     *           }
     *         }
     *       ]
     *     }
     *   ],
     *   "links": {
     *     "first": "https://eav.ivan-larionov.dev/api/projects?page=1",
     *     "last": "https://eav.ivan-larionov.dev/api/projects?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "first_page_url": "https://eav.ivan-larionov.dev/api/projects?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "https://eav.ivan-larionov.dev/api/projects?page=1",
     *   "next_page_url": null,
     *   "path": "https://eav.ivan-larionov.dev/api/projects",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "to": 10,
     *   "total": 15
     * }
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
     * Store a newly created project. Available for all users
     * 
     * @group Projects
     * @bodyParam name string required The name of the project.
     * @bodyParam status int required The status of the project.
     * @bodyParam user_ids array required The IDs of the users to assign to the project.
     * @bodyParam attributes array required The attributes of the project. Each array item must have a name of already existing attribute and value.
     * @authenticated
     * @response 201 {
     *   "message": "Project created successfully",
     *   "project": {
     *     "id": 1,
     *     "name": "Project 1",
     *     "status": 1,
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00",
     *     "users": [
     *       {
     *         "id": 1,
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "email": "user1@example.com",
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00"
     *       }
     *     ],
     *     "attribute_values": [
     *       {
     *         "id": 1,
     *         "attribute_id": 1,
     *         "entity_id": 1,
     *         "value": "Value 1",
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00",
     *         "attribute_name": "department",
     *         "attribute": {
     *           "id": 1,
     *           "name": "Attribute 1",
     *           "type": "text"
     *         }
     *       }
     *     ]
     *   }
     * }
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
     * Display a specific project. Available only for projects assigned to the authenticated user.
     * 
     * @group Projects
     * @urlParam id int required The ID of the project.
     * @authenticated
     * @response 200 {
     *   "id": 1,
     *   "name": "Project 1",
     *   "status": 1,
     *   "created_at": "2025-01-01 12:00:00",
     *   "updated_at": "2025-01-01 12:00:00",
     *   "users": [
     *     {
     *       "id": 1,
     *       "first_name": "John",
     *       "last_name": "Doe",
     *       "email": "user1@example.com",
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00"
     *     }
     *   ],
     *   "attribute_values": [
     *     {
     *       "id": 1,
     *       "attribute_id": 1,
     *       "entity_id": 1,
     *       "value": "Value 1",
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00",
     *       "attribute_name": "department",
     *       "attribute": {
     *         "id": 1,
     *         "name": "Attribute 1",
     *         "type": "text"
     *       }
     *     }
     *   ]
     * }
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
     * Update a project. Available only for projects assigned to the authenticated user.
     * 
     * @group Projects
     * @urlParam id int required The ID of the project.
     * @authenticated
     * @response 200 {
     *   "message": "Project updated successfully",
     *   "project": {
     *     "id": 1,
     *     "name": "Project 1",
     *     "status": 1,
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00",
     *     "users": [
     *       {
     *         "id": 1,
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "email": "user1@example.com",
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00"
     *       }
     *     ],
     *     "attribute_values": [
     *       {
     *         "id": 1,
     *         "attribute_id": 1,
     *         "entity_id": 1,
     *         "value": "Value 1",
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00",
     *         "attribute_name": "department",
     *         "attribute": {
     *           "id": 1,
     *           "name": "Attribute 1",
     *           "type": "text"
     *         }
     *       }
     *     ]
     *   }
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
     * Remove a project. Available only for projects assigned to the authenticated user.
     * 
     * @group Projects
     * @urlParam id int required The ID of the project.
     * @authenticated
     * @response 200 {
     *   "message": "Project deleted successfully"
     * }
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