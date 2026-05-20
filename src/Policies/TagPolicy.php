<?php

namespace Minishop\Policies;

use Minishop\Models\Tag;
use Minishop\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tags.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('tags.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tags.create');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('tags.update');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('tags.delete');
    }
}
