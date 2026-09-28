<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * docs/SAFIFX.md §12's "Today" dashboard panel.
 *
 * Money figures are summed for KES-sourced corridors only, since that is
 * SafiFX's home currency and consolidating mixed currencies into one number
 * would require a live conversion this MVP doesn't otherwise need.
 *
 * The status stats link straight into the matching tab on the transactions
 * list (ListTransactions::getTabs()), so an operator can go from "3 need
 * attention" to that exact filtered list in one click.
 */
class TodayOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Transaction::query()->today();

        $awaitingVerificationCount = (clone $today)->whereIn('status', [
            TransactionStatus::PaymentAwaiting,
            TransactionStatus::PaymentSubmitted,
        ])->count();

        $processingCount = (clone $today)->whereIn('status', [
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
            Stat::make('Transactions Today', (clone $today)->count())
                ->description('All statuses')
                ->descriptionIcon('heroicon-m-calendar-days', IconPosition::After)
                ->color('gray')
                ->url(TransactionResource::getUrl('index', ['tab' => 'all'])),

            Stat::make('Awaiting Verification', $awaitingVerificationCount)
                ->description('Needs Confirm/Reject Payment • Click to review')
                ->descriptionIcon('heroicon-m-clock', IconPosition::After)
                ->color($awaitingVerificationCount > 0 ? 'warning' : 'gray')
                ->url(TransactionResource::getUrl('index', ['tab' => 'awaiting_verification'])),

            Stat::make('Verified / Processing', $processingCount)
                ->description('Needs Mark As Paid • Click to process')
                ->descriptionIcon('heroicon-m-arrow-path', IconPosition::After)
                ->color($processingCount > 0 ? 'info' : 'gray')
                ->url(TransactionResource::getUrl('index', ['tab' => 'processing'])),

            Stat::make('Completed', $completedCount)
                ->description('Delivered to recipients today')
                ->descriptionIcon('heroicon-m-check-badge', IconPosition::After)
                ->color('success')
                ->url(TransactionResource::getUrl('index', ['tab' => 'completed'])),

            Stat::make('Failed', $failedCount)
                ->description('Rejected, cancelled, or refunded')
                ->descriptionIcon('heroicon-m-x-circle', IconPosition::After)
                ->color($failedCount > 0 ? 'danger' : 'gray')
                ->url(TransactionResource::getUrl('index', ['tab' => 'failed'])),

            Stat::make('Money Received (KES)', number_format((float) $moneyReceived, 2))
                ->description('Total customer payments today')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('gray'),

            Stat::make('Money Paid Out (KES Equivalent)', number_format((float) $moneyPaidOut, 2))
                ->description('Delivered to recipients today')
                ->descriptionIcon('heroicon-m-arrow-up-right', IconPosition::After)
                ->color('gray'),

            Stat::make('Fees Collected (KES)', number_format((float) $fees, 2))
                ->description('SafiFX fee revenue today')
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::After)
                ->color('gray'),

            Stat::make('FX Revenue (KES)', number_format($fxRevenue, 2))
                ->description('Spread between customer & market rate')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::After)
                ->color('gray'),
        ];
    }
}
