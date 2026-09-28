<?php

namespace App\Policies;

use App\Models\LiquidityBalance;
use App\Models\User;

/**
 * docs/SAFIFX.md §12/§17: both Super Admins and Operators can view liquidity;
 * only a Super Admin can add or change balances.
 */
class LiquidityBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, LiquidityBalance $liquidityBalance): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, LiquidityBalance $liquidityBalance): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, LiquidityBalance $liquidityBalance): bool
    {
        return $user->isSuperAdmin();
    }
}
