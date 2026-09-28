<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    /**
     * Grouped by which admin action (if any) the transaction is waiting on —
     * "Awaiting Verification" needs Confirm/Reject Payment, "Verified /
     * Processing" needs Mark As Paid (docs/SAFIFX.md §14/§15).
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => Transaction::count()),

            'awaiting_verification' => Tab::make('Awaiting Verification')
                ->badge(fn () => Transaction::where('status', TransactionStatus::PaymentSubmitted)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatus::PaymentSubmitted)),

            'processing' => Tab::make('Verified / Processing')
                ->badge(fn () => Transaction::whereIn('status', [
                    TransactionStatus::PaymentVerified,
                    TransactionStatus::PayoutProcessing,
                ])->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    TransactionStatus::PaymentVerified,
                    TransactionStatus::PayoutProcessing,
                ])),

            'completed' => Tab::make('Completed')
                ->badge(fn () => Transaction::where('status', TransactionStatus::Completed)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatus::Completed)),

            'failed' => Tab::make('Failed')
                ->badge(fn () => Transaction::whereIn('status', [
                    TransactionStatus::Failed,
                    TransactionStatus::Cancelled,
                    TransactionStatus::Refunded,
                ])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    TransactionStatus::Failed,
                    TransactionStatus::Cancelled,
                    TransactionStatus::Refunded,
                ])),
        ];
    }
}
