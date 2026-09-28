<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Implementing Filament's HasColor/HasIcon/HasLabel contracts means every
 * ->badge() column or entry anywhere in the admin (table, infolist, filter
 * options) automatically picks up the right label, color, and icon with no
 * per-usage ->formatStateUsing()/->color() wiring.
 */
enum TransactionStatus: string implements HasColor, HasIcon, HasLabel
{
    case PaymentAwaiting = 'payment_awaiting';
    case PaymentSubmitted = 'payment_submitted';
    case PaymentVerified = 'payment_verified';
    case PayoutProcessing = 'payout_processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
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

    public function getColor(): string
    {
        return match ($this) {
            self::PaymentAwaiting, self::PaymentSubmitted => 'gray',
            self::PaymentVerified, self::PayoutProcessing => 'warning',
            self::Completed => 'success',
            self::Failed, self::Cancelled, self::Refunded => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PaymentAwaiting => 'heroicon-m-clock',
            self::PaymentSubmitted => 'heroicon-m-paper-airplane',
            self::PaymentVerified => 'heroicon-m-check-circle',
            self::PayoutProcessing => 'heroicon-m-arrow-path',
            self::Completed => 'heroicon-m-check-badge',
            self::Failed => 'heroicon-m-x-circle',
            self::Cancelled => 'heroicon-m-no-symbol',
            self::Refunded => 'heroicon-m-arrow-uturn-left',
        };
    }

    /**
     * Plain-string accessors for callers outside Filament components — the
     * CSV export, the customer-facing /track page, tests — that need a
     * string rather than Filament's string|Htmlable union.
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
