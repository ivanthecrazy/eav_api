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
     * List all timesheets.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $timesheets = Timesheet::with(['user', 'project'])->where('user_id', Auth::id())->paginate($perPage);
        return response()->json($timesheets);
    }

    /**
     * Store a new timesheet record.
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
     * Display a single timesheet record.
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
     * Update a timesheet record.
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
     * Delete a timesheet record.
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
