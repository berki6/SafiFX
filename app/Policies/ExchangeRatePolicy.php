<?php

namespace App\Policies;

use App\Models\ExchangeRate;
use App\Models\User;

/**
 * docs/SAFIFX.md §19: "Operators should not be able to change exchange
 * rates or critical system settings" — rates are Super Admin only.
 */
class ExchangeRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->isSuperAdmin();
    }
}
