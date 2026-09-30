<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Project $project): bool
    {
        if ($user?->can('projects.edit')) {
            return true;
        }

        return $project->status === 'published'
            && $project->published_at !== null
            && $project->published_at->isPast();
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.edit');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete');
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}