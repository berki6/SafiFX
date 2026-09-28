<?php

namespace App\Policies;

use App\Models\User;

/**
 * docs/SAFIFX.md §19: "Manage admins" is listed as a Super Admin capability
 * only — it is not among the Operator's permissions. Without this policy,
 * Filament left UserResource open to any panel user (is_admin), letting an
 * Operator create, edit, or delete other admin accounts.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
}
