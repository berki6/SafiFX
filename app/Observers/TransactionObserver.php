<?php

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Logs the status-change events docs/SAFIFX.md §21 asks to notify customers on.
 *
 * The spec asks for email/SMS/WhatsApp delivery, but the customer form (§8) never
 * collects an email address and §24 explicitly excludes telecom API integration
 * from the MVP. Until a real channel exists, this observer logs the exact copy
 * spec §21 gives, at the same trigger points a real notification would use, so
 * swapping in `Notification::send(...)` later is a one-line change per event.
 */
class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        Log::info("Your SafiFX transaction {$transaction->reference} has been created. Please complete payment using the instructions provided.", [
            'transaction_id' => $transaction->id,
            'event' => 'transaction.created',
        ]);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged('status')) {
            return;
        }

        match ($transaction->status) {
            TransactionStatus::PaymentVerified => Log::info(
                'Your payment has been verified. Your SafiFX payout is now being processed.',
                ['transaction_id' => $transaction->id, 'event' => 'transaction.payment_verified'],
            ),
            TransactionStatus::Completed => Log::info(
                "Your SafiFX transfer is complete. {$transaction->to_currency} {$transaction->recipient_amount} has been sent to the recipient.",
                ['transaction_id' => $transaction->id, 'event' => 'transaction.completed'],
            ),
            default => null,
        };
    }
}
