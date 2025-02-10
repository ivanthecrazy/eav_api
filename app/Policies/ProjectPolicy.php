<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their assigned projects
    }

    /**
     * Determine if the user can view a specific project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can create a project.
     */
    public function create(User $user): bool
    {
        return true; // Allow any authenticated user to create a project
    }

    /**
     * Determine if the user can update a project.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can delete a project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
