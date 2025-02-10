<?php

namespace App\Policies;

use App\Models\Timesheet;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class TimesheetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Timesheet $timesheet): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can update the timesheet.
     */
    public function update(User $user, Timesheet $timesheet): bool
    {
        return $user->id === $timesheet->user_id;
    }

    /**
     * Determine if the user can delete the timesheet.
     */
    public function delete(User $user, Timesheet $timesheet): bool
    {
        return $user->id === $timesheet->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Timesheet $timesheet): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Timesheet $timesheet): bool
    {
        return false;
    }
}
