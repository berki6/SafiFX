<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('ID')
                    ->weight('bold')
                    ->icon(Heroicon::OutlinedHashtag)
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('from_currency')
                    ->label('From')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('to_currency')
                    ->label('To')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('amount_sent')
                    ->label('Sent')
                    ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->amount_sent, 2).' '.$record->from_currency),
                TextColumn::make('recipient_amount')
                    ->label('Receive')
                    ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->recipient_amount, 2).' '.$record->to_currency),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(TransactionStatus::class),
                SelectFilter::make('from_currency')
                    ->label('From Currency')
                    ->options(fn () => Transaction::query()->distinct()->pluck('from_currency', 'from_currency')),
                SelectFilter::make('to_currency')
                    ->label('To Currency')
                    ->options(fn () => Transaction::query()->distinct()->pluck('to_currency', 'to_currency')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
