<?php

namespace App\Filament\Resources\LiquidityBalances\Tables;

use App\Models\LiquidityBalance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LiquidityBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('available_amount')
                    ->label('Available')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (LiquidityBalance $record) => $record->isLow() ? 'Low' : 'Good')
                    ->badge()
                    ->color(fn (LiquidityBalance $record) => $record->isLow() ? 'danger' : 'success'),
            ])
            ->defaultSort('currency')
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
