<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('customer_name')->label('Customer')->weight('semibold'),
                            TextEntry::make('customer_phone')
                                ->label('Customer Phone')
                                ->icon(Heroicon::OutlinedPhone)
                                ->copyable()
                                ->copyMessage('Phone number copied'),
                            TextEntry::make('customer_email')
                                ->label('Customer Email')
                                ->icon(Heroicon::OutlinedEnvelope)
                                ->copyable()
                                ->copyMessage('Email copied')
                                ->placeholder('—'),
                        ]),
                    ]),

                Section::make('Transfer')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('from_currency')->label('From')->badge()->color('gray'),
                            TextEntry::make('to_currency')->label('To')->badge()->color('gray'),
                            TextEntry::make('total_paid')
                                ->label('Customer Paid')
                                ->weight('bold')
                                ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->total_paid, 2).' '.$record->from_currency),
                            TextEntry::make('exchange_rate')
                                ->label('Exchange Rate')
                                ->formatStateUsing(fn (Transaction $record) => "1 {$record->from_currency} = {$record->exchange_rate} {$record->to_currency}"),
                            TextEntry::make('recipient_amount')
                                ->label('Recipient Amount')
                                ->weight('bold')
                                ->formatStateUsing(fn (Transaction $record) => number_format((float) $record->recipient_amount, 2).' '.$record->to_currency),
                        ]),
                    ]),

                Section::make('Recipient')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('recipient_name')->label('Recipient')->weight('semibold'),
                            TextEntry::make('recipient_phone')
                                ->label('Recipient Phone')
                                ->icon(Heroicon::OutlinedPhone)
                                ->copyable()
                                ->copyMessage('Phone number copied'),
                            TextEntry::make('recipient_network')->label('Network')->badge()->color('gray'),
                        ]),
                    ]),

                Section::make('Payment')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('payment_method')
                                ->label('Payment Method')
                                ->state(fn (Transaction $record) => $record->paymentMethod() ?? '—'),
                            TextEntry::make('payment_reference')
                                ->label('Transaction Code')
                                ->icon(Heroicon::OutlinedReceiptPercent)
                                ->copyable()
                                ->copyMessage('Transaction code copied'),
                            TextEntry::make('status')
                                ->label('Payment Status')
                                ->badge(),
                        ]),
                    ]),

                Section::make('Payout')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('payout_reference')
                                ->label('Payout Reference')
                                ->icon(Heroicon::OutlinedReceiptPercent)
                                ->copyable()
                                ->copyMessage('Payout reference copied')
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
                    ->icon(Heroicon::OutlinedIdentification)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('processedBy.name')
                            ->label('Admin Who Processed It')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
