<?php

namespace App\Policies;

use App\Models\Country;
use App\Models\User;

/**
 * docs/SAFIFX.md §19: countries and mobile-money settings can only be
 * managed by a Super Admin — Operators may not touch them.
 */
class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Country $country): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Country $country): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->isSuperAdmin();
    }
}
