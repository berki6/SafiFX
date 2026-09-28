<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency Pair')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('from_currency')
                                ->label('From Currency')
                                ->required()
                                ->length(3)
                                ->alpha()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('from_currency', strtoupper((string) $state)))
                                ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                                ->validationMessages([
                                    'length' => 'The currency code must be exactly 3 letters, e.g. KES.',
                                    'alpha' => 'The currency code may only contain letters.',
                                ])
                                ->helperText('3-letter code, e.g. KES.'),
                            TextInput::make('to_currency')
                                ->label('To Currency')
                                ->required()
                                ->length(3)
                                ->alpha()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('to_currency', strtoupper((string) $state)))
                                ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                                ->rule(
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if (strtoupper((string) $value) === strtoupper((string) $get('from_currency'))) {
                                            $fail('The "to" currency must be different from the "from" currency.');
                                        }
                                    },
                                )
                                ->unique(
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('from_currency', strtoupper((string) $get('from_currency'))),
                                )
                                ->validationMessages([
                                    'length' => 'The currency code must be exactly 3 letters, e.g. UGX.',
                                    'alpha' => 'The currency code may only contain letters.',
                                    'unique' => 'A rate for this currency pair already exists — edit that one instead of creating a duplicate.',
                                ])
                                ->helperText('3-letter code, e.g. UGX.'),
                        ]),
                    ]),

                Section::make('Rate & Fee')
                    ->description('docs/SAFIFX.md §6 — the customer never enters a rate; the calculator always uses this configuration.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('rate')
                                ->label('Customer Rate')
                                ->numeric()
                                ->required()
                                ->rule('gt:0')
                                ->validationMessages(['gt' => 'The customer rate must be greater than zero.'])
                                ->helperText('What the customer actually gets, e.g. 28.00.'),
                            TextInput::make('market_rate')
                                ->label('Market / Reference Rate')
                                ->numeric()
                                ->nullable()
                                ->rule('gt:0')
                                ->validationMessages(['gt' => 'The market rate must be greater than zero.'])
                                ->helperText('Optional — used to compute FX revenue on the dashboard.'),
                            TextInput::make('fee')
                                ->label('Transaction Fee')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->rule('gte:0')
                                ->validationMessages(['gte' => "The fee can't be negative."])
                                ->helperText('Flat fee in the "from" currency, charged on top of the amount sent.'),
                        ]),
                    ]),

                Section::make('Transaction Limits')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('min_amount')
                                ->label('Minimum')
                                ->numeric()
                                ->nullable()
                                ->rule('gte:0')
                                ->validationMessages(['gte' => "The minimum can't be negative."])
                                ->live(onBlur: true),
                            TextInput::make('max_amount')
                                ->label('Maximum')
                                ->numeric()
                                ->nullable()
                                ->rule('gte:0')
                                ->rule(
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $min = $get('min_amount');

                                        if ($min !== null && $min !== '' && (float) $value < (float) $min) {
                                            $fail('The maximum must be greater than or equal to the minimum.');
                                        }
                                    },
                                )
                                ->validationMessages(['gte' => "The maximum can't be negative."]),
                        ]),
                    ]),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
