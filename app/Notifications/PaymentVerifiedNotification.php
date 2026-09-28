<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * docs/SAFIFX.md §21 — sent once a SafiFX operator confirms the customer's
 * payment (TransactionResource's "Confirm Payment" action).
 */
class PaymentVerifiedNotification extends Notification
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
            ->subject("Payment verified — {$this->transaction->reference}")
            ->greeting('Hi '.$this->transaction->customer_name.',')
            ->line('Your payment has been verified. Your SafiFX payout is now being processed.');
    }
}
