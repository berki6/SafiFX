<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * docs/SAFIFX.md §12's "Today" dashboard panel.
 *
 * Money figures are summed for KES-sourced corridors only, since that is
 * SafiFX's home currency and consolidating mixed currencies into one number
 * would require a live conversion this MVP doesn't otherwise need.
 */
class TodayOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Transaction::query()->today();

        $pendingCount = (clone $today)->whereIn('status', [
            TransactionStatus::PaymentAwaiting,
            TransactionStatus::PaymentSubmitted,
            TransactionStatus::PaymentVerified,
            TransactionStatus::PayoutProcessing,
        ])->count();

        $completedCount = (clone $today)->where('status', TransactionStatus::Completed)->count();

        $failedCount = (clone $today)->whereIn('status', [
            TransactionStatus::Failed,
            TransactionStatus::Cancelled,
            TransactionStatus::Refunded,
        ])->count();

        $kesToday = (clone $today)->where('from_currency', 'KES');

        $moneyReceived = (clone $kesToday)->sum('total_paid');
        // recipient_amount = amount_sent * rate (see ExchangeRate::recipientAmountFor()), so
        // amount_sent is already the KES equivalent of what a completed payout delivered.
        $moneyPaidOut = (clone $kesToday)->where('status', TransactionStatus::Completed)->sum('amount_sent');
        $fees = (clone $kesToday)->sum('fee');
        $fxRevenue = (clone $kesToday)
            ->whereNotNull('market_rate')
            ->get()
            ->sum(fn (Transaction $transaction) => (float) $transaction->amount_sent * ((float) $transaction->market_rate - (float) $transaction->exchange_rate));

        return [
            Stat::make('Transactions Today', (clone $today)->count()),
            Stat::make('Pending', $pendingCount),
            Stat::make('Completed', $completedCount),
            Stat::make('Failed', $failedCount),
            Stat::make('Money Received (KES)', number_format((float) $moneyReceived, 2)),
            Stat::make('Money Paid Out (KES Equivalent)', number_format((float) $moneyPaidOut, 2)),
            Stat::make('Fees Collected (KES)', number_format((float) $fees, 2)),
            Stat::make('FX Revenue (KES)', number_format($fxRevenue, 2)),
        ];
    }
}
