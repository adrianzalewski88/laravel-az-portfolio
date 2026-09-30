<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('projects.edit');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('projects.edit');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('projects.delete');
    }
}