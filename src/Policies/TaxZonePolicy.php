<?php

namespace Minishop\Policies;

use Minishop\Models\TaxZone;
use Minishop\Models\User;

/**
 * Authorization policy for Tax Zone management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'tax-zones.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 *
 * TaxZoneRateController actions are also authorized through this policy
 * via the parent zone (createRate, updateRate, deleteRate).
 */
class TaxZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tax-zones.view');
    }

    public function view(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tax-zones.create');
    }

    public function update(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.update');
    }

    public function delete(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.delete');
    }

    public function createRate(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.create');
    }

    public function updateRate(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.update');
    }

    public function deleteRate(User $user, TaxZone $taxZone): bool
    {
        return $user->can('tax-zones.delete');
    }
}
