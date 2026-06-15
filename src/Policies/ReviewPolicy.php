<?php

namespace Minishop\Policies;

use Minishop\Models\Review;
use Minishop\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can('reviews.view');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.update');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->can('reviews.delete');
    }
}
