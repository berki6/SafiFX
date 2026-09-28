<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_admin')
                    ->label('Administrator Access')
                    ->live()
                    ->default(false),
                Select::make('role')
                    ->label('Admin Role')
                    ->options(UserRole::class)
                    ->visible(fn (Get $get): bool => (bool) $get('is_admin'))
                    ->required(fn (Get $get): bool => (bool) $get('is_admin'))
                    ->helperText('Super Admins manage rates, fees, and countries. Operators only process payouts.'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                DateTimePicker::make('email_verified_at'),
            ]);
    }
}
