<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/**
 * docs/SAFIFX.md §12's "Today" dashboard panel.
 *
 * SafiFX's corridors run both directions (KES→UGX and UGX→KES both exist),
 * so money figures are broken down per currency rather than assumed to be
 * KES-sourced — an earlier version hard-filtered to from_currency = 'KES',
 * which silently zeroed out every stat for a transaction in the other
 * direction. There's no single reporting currency to convert into without
 * fabricating a rate for pairs that don't have one, so each stat shows a
 * "CUR amount" breakdown per currency actually involved today instead of
 * one blended number.
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

        // Money received & fees are charged in from_currency (what the customer paid in).
        $moneyReceived = (clone $today)
            ->selectRaw('from_currency, SUM(total_paid) as total')
            ->groupBy('from_currency')
            ->pluck('total', 'from_currency');

        $fees = (clone $today)
            ->selectRaw('from_currency, SUM(fee) as total')
            ->groupBy('from_currency')
            ->pluck('total', 'from_currency');

        // Money paid out is what actually reached recipients — to_currency, completed only.
        $moneyPaidOut = (clone $today)
            ->where('status', TransactionStatus::Completed)
            ->selectRaw('to_currency, SUM(recipient_amount) as total')
            ->groupBy('to_currency')
            ->pluck('total', 'to_currency');

        // FX revenue (the customer-rate vs market-rate spread × volume) is realized in
        // from_currency, same as the fee.
        $fxRevenue = (clone $today)
            ->whereNotNull('market_rate')
            ->get()
            ->groupBy('from_currency')
            ->map(fn (Collection $transactions) => $transactions->sum(
                fn (Transaction $transaction) => (float) $transaction->amount_sent * ((float) $transaction->market_rate - (float) $transaction->exchange_rate)
            ));

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

            Stat::make('Money Received', $this->formatByCurrency($moneyReceived))
                ->description('Total customer payments today')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('gray'),

            Stat::make('Money Paid Out', $this->formatByCurrency($moneyPaidOut))
                ->description('Delivered to recipients today')
                ->descriptionIcon('heroicon-m-arrow-up-right', IconPosition::After)
                ->color('gray'),

            Stat::make('Fees Collected', $this->formatByCurrency($fees))
                ->description('SafiFX fee revenue today')
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::After)
                ->color('gray'),

            Stat::make('FX Revenue', $this->formatByCurrency($fxRevenue))
                ->description('Spread between customer & market rate')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::After)
                ->color('gray'),
        ];
    }

    /**
     * @param  Collection<array-key, mixed>  $totalsByCurrency
     */
    private function formatByCurrency(Collection $totalsByCurrency): string
    {
        if ($totalsByCurrency->isEmpty()) {
            return '0.00';
        }

        return $totalsByCurrency
            ->map(fn ($total, $currency) => "{$currency} ".number_format((float) $total, 2))
            ->implode(' · ');
    }
}
