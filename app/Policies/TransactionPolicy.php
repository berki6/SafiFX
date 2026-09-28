<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * docs/SAFIFX.md §14/§15/§19 — the admin transaction workflow. Transactions
 * are created by customers via the send-money flow, never by admins, and are
 * only ever mutated through the Confirm/Reject/Mark-As-Paid actions.
 */
class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Only a Super Admin can verify a customer's payment (spec §19: SuperAdmin
     * "Verify payments" is not among the Operator's listed permissions).
     */
    public function confirmPayment(User $user, Transaction $transaction): bool
    {
        return $user->isSuperAdmin();
    }

    public function rejectPayment(User $user, Transaction $transaction): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Both roles can process a payout and enter its reference (spec §19).
     */
    public function markAsPaid(User $user, Transaction $transaction): bool
    {
        return $user->is_admin;
    }
}
