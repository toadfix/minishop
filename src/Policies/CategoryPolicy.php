<?php

namespace Minishop\Policies;

use Minishop\Models\Category;
use Minishop\Models\User;

/**
 * Authorization policy for Category management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'categories.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }
}
