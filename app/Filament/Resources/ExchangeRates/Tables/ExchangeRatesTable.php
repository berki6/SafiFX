<?php

namespace App\Filament\Resources\ExchangeRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_currency')
                    ->label('From')
                    ->badge()
                    ->sortable(),
                TextColumn::make('to_currency')
                    ->label('To')
                    ->badge()
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Customer Rate')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('fee')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('min_amount')
                    ->label('Min')
                    ->numeric(decimalPlaces: 0)
                    ->toggleable(),
                TextColumn::make('max_amount')
                    ->label('Max')
                    ->numeric(decimalPlaces: 0)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('from_currency')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
