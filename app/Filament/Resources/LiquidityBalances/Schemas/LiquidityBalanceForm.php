<?php

namespace App\Filament\Resources\LiquidityBalances\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LiquidityBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('currency')
                        ->required()
                        ->length(3)
                        ->alpha()
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('currency', strtoupper((string) $state)))
                        ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                        ->validationMessages([
                            'length' => 'The currency code must be exactly 3 letters, e.g. UGX.',
                            'alpha' => 'The currency code may only contain letters.',
                            'unique' => 'A liquidity balance for this currency already exists — edit that one instead.',
                        ])
                        ->helperText('3-letter currency code, e.g. UGX.'),
                    TextInput::make('available_amount')
                        ->label('Available Amount')
                        ->numeric()
                        ->required()
                        ->rule('gte:0')
                        ->validationMessages(['gte' => "The available amount can't be negative."]),
                    TextInput::make('low_threshold')
                        ->label('Low Threshold')
                        ->numeric()
                        ->nullable()
                        ->rule('gte:0')
                        ->validationMessages(['gte' => "The low threshold can't be negative."])
                        ->helperText('Balances at or below this are flagged "Low".'),
                ]),
            ]);
    }
}
