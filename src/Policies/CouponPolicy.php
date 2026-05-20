<?php

namespace Minishop\Policies;

use Minishop\Models\Coupon;
use Minishop\Models\User;

/**
 * Authorization policy for Coupon management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'coupons.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.update');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.delete');
    }
}
