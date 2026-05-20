<?php

namespace Minishop\Policies;

use Minishop\Models\Order;
use Minishop\Models\User;

/**
 * Authorization policy for Order management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'orders.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class OrderPolicy
{
    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    /**
     * Allow a customer to view their own order via the API.
     */
    public function viewOwn(User $user, Order $order): bool
    {
        return $user->customer?->id === $order->customer_id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function invoice(User $user, Order $order): bool
    {
        return $user->can('orders.invoice');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }
}
