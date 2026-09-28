<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PaymentAwaiting = 'payment_awaiting';
    case PaymentSubmitted = 'payment_submitted';
    case PaymentVerified = 'payment_verified';
    case PayoutProcessing = 'payout_processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PaymentAwaiting => 'Payment Awaiting',
            self::PaymentSubmitted => 'Payment Submitted',
            self::PaymentVerified => 'Payment Verified',
            self::PayoutProcessing => 'Payout Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PaymentAwaiting, self::PaymentSubmitted => 'gray',
            self::PaymentVerified, self::PayoutProcessing => 'warning',
            self::Completed => 'success',
            self::Failed, self::Cancelled, self::Refunded => 'danger',
        };
    }
}
