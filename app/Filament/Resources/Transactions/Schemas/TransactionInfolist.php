<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('customer_name')->label('Customer'),
                            TextEntry::make('customer_phone')->label('Customer Phone'),
                            TextEntry::make('customer_email')->label('Customer Email')->placeholder('—'),
                        ]),
                    ]),

                Section::make('Transfer')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('from_currency')->label('From'),
                            TextEntry::make('to_currency')->label('To'),
                            TextEntry::make('total_paid')
                                ->label('Customer Paid')
                                ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->total_paid, 2).' '.$record->from_currency),
                            TextEntry::make('exchange_rate')
                                ->label('Exchange Rate')
                                ->formatStateUsing(fn (Transaction $record) => "1 {$record->from_currency} = {$record->exchange_rate} {$record->to_currency}"),
                            TextEntry::make('recipient_amount')
                                ->label('Recipient Amount')
                                ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->recipient_amount, 2).' '.$record->to_currency),
                        ]),
                    ]),

                Section::make('Recipient')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('recipient_name')->label('Recipient'),
                            TextEntry::make('recipient_phone')->label('Recipient Phone'),
                            TextEntry::make('recipient_network')->label('Network'),
                        ]),
                    ]),

                Section::make('Payment')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('payment_method')
                                ->label('Payment Method')
                                ->state(fn (Transaction $record) => $record->paymentMethod() ?? '—'),
                            TextEntry::make('payment_reference')->label('Transaction Code'),
                            TextEntry::make('status')
                                ->label('Payment Status')
                                ->badge()
                                ->formatStateUsing(fn (Transaction $record) => $record->status->label())
                                ->color(fn (Transaction $record) => $record->status->color()),
                        ]),
                    ]),

                Section::make('Payout')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('payout_reference')
                                ->label('Payout Reference')
                                ->placeholder('Awaiting Payout'),
                            TextEntry::make('payment_verified_at')
                                ->label('Payment Verified At')
                                ->dateTime()
                                ->placeholder('—'),
                            TextEntry::make('completed_at')
                                ->label('Completed At')
                                ->dateTime()
                                ->placeholder('—'),
                        ]),
                    ]),

                Section::make('Processed By')
                    ->schema([
                        TextEntry::make('processedBy.name')
                            ->label('Admin Who Processed It')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
