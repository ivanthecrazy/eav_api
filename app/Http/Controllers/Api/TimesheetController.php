<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Ramsey\Uuid\Type\Time;

class TimesheetController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a list of all timesheets for the authenticated user.
     * 
     * @group Timesheets
     * @queryParam per_page int The number of timesheets to return per page.
     * @authenticated
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "task_name": "Some task name",
     *       "date": "2025-01-01",
     *       "hours": 4.5,
     *       "project_id": 1,
     *       "user_id": 1,
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00",
     *       "project": {
     *         "id": 1,
     *         "name": "Some project name",
     *         "status": 1,
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00"
     *       },
     *       "user": {
     *         "id": 1,
     *         "first_name": "Some user name",
     *         "last_name": "Some user last name",
     *         "email": "some@email.com",
     *         "created_at": "2025-01-01 12:00:00",
     *         "updated_at": "2025-01-01 12:00:00"
     *       }
     *     }
     *   ],
     *   "links": {
     *     "first": "https://eav.ivan-larionov.dev/api/timesheets?page=1",
     *     "last": "https://eav.ivan-larionov.dev/api/timesheets?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "first_page_url": "https://eav.ivan-larionov.dev/api/timesheets?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "https://eav.ivan-larionov.dev/api/timesheets?page=1",
     *   "next_page_url": null,
     *   "path": "https://eav.ivan-larionov.dev/api/timesheets",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "to": 10,
     *   "total": 15
     * }
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $timesheets = Timesheet::with(['user', 'project'])->where('user_id', Auth::id())->paginate($perPage);
        return response()->json($timesheets);
    }

    /**
     * Store a new timesheet record for the authenticated user. Available only for users assigned to the project.
     * 
     * @group Timesheets
     * @bodyParam task_name string required The name of the task.
     * @bodyParam date date required The date of the timesheet.
     * @bodyParam hours float required The number of hours worked.
     * @bodyParam project_id int required The ID of the project.
     * @authenticated
     * @response 201 {
     *   "message": "Timesheet created successfully",
     *   "timesheet": {
     *     "id": 1,
     *     "task_name": "Some task name",
     *     "date": "2025-01-01",
     *     "hours": 4.5,
     *     "project_id": 1,
     *     "user_id": 1,
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00", 
     *     "project": {
     *       "id": 1,
     *       "name": "Some project name",
     *       "status": 1,
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00"
     *     },
     *     "user": {
     *       "id": 1,
     *       "first_name": "Some user name",
     *       "last_name": "Some user last name",
     *       "email": "some@email.com",
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00"
     *     }
     *   }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'project_id' => 'required|exists:projects,id',
        ]);
        $project = Project::find($validated['project_id']);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $this->authorize('create', [Timesheet::class, $project]);

        $timesheet = Timesheet::create([
            'task_name' => $validated['task_name'],
            'date' => $validated['date'],
            'hours' => $validated['hours'],
            'user_id' => Auth::id(),
            'project_id' => $validated['project_id'],
        ]);

        return response()->json([
            'message' => 'Timesheet created successfully',
            'timesheet' => $timesheet->load('user', 'project')
        ], 201);
    }

    /**
     * Display a single timesheet record. Available only for timesheets assigned to the authenticated user.
     * 
     * @group Timesheets
     * @authenticated
     * @urlParam id int required The ID of the timesheet.
     * @response 200 {
     *   "id": 1,
     *   "task_name": "Some task name",
     *   "date": "2025-01-01",
     *   "hours": 4.5,
     *   "project_id": 1,
     *   "user_id": 1,
     *   "created_at": "2025-01-01 12:00:00",
     *   "updated_at": "2025-01-01 12:00:00",
     *   "project": {
     *     "id": 1,
     *     "name": "Some project name",
     *     "status": 1,
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00",
     *   },
     *   "user": {
     *     "id": 1,
     *     "first_name": "Some user name",
     *     "last_name": "Some user last name",
     *     "email": "some@email.com",
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00"
     *   }
     * }
     */
    public function show($id)
    {
        $timesheet = Timesheet::with(['user', 'project'])->where('user_id', Auth::id())->find($id);

        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found'], 404);
        }

        return response()->json($timesheet);
    }

    /**
     * Update a timesheet record. Available only for timesheets assigned to the authenticated user.
     * 
     * @group Timesheets
     * @urlParam id int required The ID of the timesheet.
     * @bodyParam task_name string required The name of the task.
     * @bodyParam date date required The date of the timesheet.
     * @bodyParam hours float required The number of hours worked.
     * @bodyParam project_id int required The ID of the project.
     * @authenticated
     * @response 200 {
     *   "message": "Timesheet updated successfully",
     *   "timesheet": {
     *     "id": 1,
     *     "task_name": "Some task name",
     *     "date": "2025-01-01",
     *     "hours": 4.5,
     *     "project_id": 1,
     *     "user_id": 1,
     *     "created_at": "2025-01-01 12:00:00",
     *     "updated_at": "2025-01-01 12:00:00",
     *     "project": {
     *       "id": 1,
     *       "name": "Some project name",
     *       "status": 1,
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00"
     *     },
     *     "user": {
     *       "id": 1,
     *       "first_name": "Some user name",
     *       "last_name": "Some user last name",
     *       "email": "some@email.com",
     *       "created_at": "2025-01-01 12:00:00",
     *       "updated_at": "2025-01-01 12:00:00"
     *     }
     *   }
     * }
     */
    public function update(Request $request, $id)
    {
        $timesheet = Timesheet::find($id);
        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found'], 404);
        }
        $this->authorize('update', $timesheet);

        $validated = $request->validate([
            'task_name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'hours' => 'sometimes|numeric|min:0.5|max:24',
            'project_id' => 'sometimes|exists:projects,id',
        ]);

        $timesheet->update($validated);

        return response()->json([
            'message' => 'Timesheet updated successfully',
            'timesheet' => $timesheet->load('user', 'project')
        ]);
    }

    /**
     * Delete a timesheet record. Available only for timesheets assigned to the authenticated user.
     * 
     * @group Timesheets
     * @urlParam id int required The ID of the timesheet.
     * @authenticated
     * @response 200 {
     *   "message": "Timesheet deleted successfully"
     * }
     */
    public function destroy($id)
    {
        $timesheet = Timesheet::find($id);
        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found'], 404);
        }
        $this->authorize('delete', $timesheet);

        $timesheet->delete();

        return response()->json(['message' => 'Timesheet deleted successfully']);
    }
}
