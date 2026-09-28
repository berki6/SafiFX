<?php

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\TransactionCompletedNotification;
use App\Notifications\TransactionCreatedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Sends the status-change notifications docs/SAFIFX.md §21 asks for, to the
 * customer's email captured on the send-money form (§8). SMS/WhatsApp are
 * deliberately not wired up: WhatsApp needs an approved Meta/Twilio business
 * account and pre-approved message templates before any code can send
 * through it, and email covers the same three events for now.
 */
class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $this->notify($transaction, new TransactionCreatedNotification($transaction));
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
            TransactionStatus::PaymentVerified => $this->notify($transaction, new PaymentVerifiedNotification($transaction)),
            TransactionStatus::Completed => $this->notify($transaction, new TransactionCompletedNotification($transaction)),
            default => null,
        };
    }

    /**
     * On-demand delivery: a Transaction has no User/Notifiable account to
     * route through, only the email address it was submitted with. Older
     * transactions (seeded/factory data from before this column existed)
     * may have none, so sending is skipped rather than failing loudly.
     */
    private function notify(Transaction $transaction, Notification $notification): void
    {
        if (! $transaction->customer_email) {
            return;
        }

        NotificationFacade::route('mail', $transaction->customer_email)->notify($notification);
    }
}
