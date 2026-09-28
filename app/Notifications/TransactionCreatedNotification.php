<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * docs/SAFIFX.md §21 — sent when a customer submits a transaction, to the
 * email captured on the send-money form (§8). Not queued: the MVP keeps
 * delivery synchronous and dependency-free; add ShouldQueue once a queue
 * worker is running in production.
 */
class TransactionCreatedNotification extends Notification
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
            ->subject("Your SafiFX transaction {$this->transaction->reference} has been created")
            ->greeting('Hi '.$this->transaction->customer_name.',')
            ->line("Your SafiFX transaction {$this->transaction->reference} has been created. Please complete payment using the instructions provided.")
            ->line("Recipient amount: {$this->transaction->to_currency} ".number_format((float) $this->transaction->recipient_amount, 2));
    }
}
