<?php

namespace Minishop\Policies;

use Minishop\Models\ShippingMethod;
use Minishop\Models\User;

/**
 * Authorization policy for Shipping Method management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'shipping-methods.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class ShippingMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shipping-methods.view');
    }

    public function view(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.view');
    }

    public function create(User $user): bool
    {
        return $user->can('shipping-methods.create');
    }

    public function update(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.update');
    }

    public function delete(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.delete');
    }
}
