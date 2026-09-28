<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * docs/SAFIFX.md §21 — sent once a SafiFX operator marks the payout as paid
 * (TransactionResource's "Mark As Paid" action).
 */
class TransactionCompletedNotification extends Notification
{
    public function __construct(private readonly Transaction $transaction) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Transfer complete — {$this->transaction->reference}")
            ->greeting('Hi '.$this->transaction->customer_name.',')
            ->line("Your SafiFX transfer is complete. {$this->transaction->to_currency} ".number_format((float) $this->transaction->recipient_amount, 2).' has been sent to the recipient.');
    }
}
