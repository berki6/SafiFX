<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Country')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('code')
                                ->label('ISO Code')
                                ->required()
                                ->length(2)
                                ->alpha()
                                ->unique(ignoreRecord: true)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('code', strtoupper((string) $state)))
                                ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                                ->validationMessages([
                                    'length' => 'The ISO code must be exactly 2 letters, e.g. KE.',
                                    'alpha' => 'The ISO code may only contain letters.',
                                    'unique' => 'Another country already uses this ISO code.',
                                ])
                                ->helperText('2-letter country code, e.g. KE.'),
                            TextInput::make('currency_code')
                                ->label('Currency Code')
                                ->required()
                                ->length(3)
                                ->alpha()
                                ->unique(ignoreRecord: true)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('currency_code', strtoupper((string) $state)))
                                ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                                ->validationMessages([
                                    'length' => 'The currency code must be exactly 3 letters, e.g. KES.',
                                    'alpha' => 'The currency code may only contain letters.',
                                    'unique' => 'Another country already uses this currency code.',
                                ])
                                ->helperText('3-letter currency code, e.g. KES. Must match exactly what the calculator and exchange rates use.'),
                            TextInput::make('flag_emoji')
                                ->label('Flag Emoji')
                                ->maxLength(8),
                        ]),
                    ])
                    ->columns(1),

                Section::make('Receiving Account')
                    ->description('The SafiFX mobile-money account customers pay into for this currency (docs/SAFIFX.md §16).')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('receiving_network')
                                ->label('Payment Network')
                                ->required()
                                ->maxLength(255)
                                ->validationMessages(['required' => 'Enter the mobile-money network customers should pay into (e.g. M-Pesa Paybill).']),
                            TextInput::make('receiving_number')
                                ->label('Receiving Number')
                                ->required()
                                ->maxLength(255)
                                ->validationMessages(['required' => "Enter SafiFX's receiving number or till for this country."]),
                            TextInput::make('receiving_account_name')
                                ->label('Account Name')
                                ->required()
                                ->maxLength(255)
                                ->validationMessages(['required' => 'Enter the account name customers will see when paying.']),
                        ]),
                    ])
                    ->columns(1),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive countries are hidden from the customer calculator.'),
            ]);
    }
}
