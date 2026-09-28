<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * docs/SAFIFX.md §12's "Today" dashboard panel — the status counts.
 *
 * The money figures (received/paid out/fees/FX revenue) live in the
 * TodayCurrencyBreakdown table widget instead of here: SafiFX's corridors
 * run both directions (KES→UGX and UGX→KES both exist), so those numbers
 * are inherently per-currency, and cramming a "KES 120,600.00 · UGX
 * 321,900.00" string into a stat card's headline value stretched cards
 * unevenly and was hard to scan. A table reads far more clearly.
 *
 * These stats link straight into the matching tab on the transactions list
 * (ListTransactions::getTabs()), so an operator can go from "3 need
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
        ];
    }
}
