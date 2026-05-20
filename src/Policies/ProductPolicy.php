<?php

namespace Minishop\Policies;

use Minishop\Models\Product;
use Minishop\Models\User;

/**
 * Authorization policy for Product management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'products.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete');
    }
}
