<?php

namespace Minishop\Policies;

use Minishop\Models\OrderReturn;
use Minishop\Models\User;

/**
 * Authorization policy for Return management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'returns.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class ReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.view');
    }

    public function create(User $user): bool
    {
        return $user->can('returns.create');
    }

    public function update(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.update');
    }

    public function approve(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.update');
    }

    public function reject(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.update');
    }

    public function receive(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.update');
    }

    public function refund(User $user, OrderReturn $orderReturn): bool
    {
        return $user->can('returns.refund');
    }
}
