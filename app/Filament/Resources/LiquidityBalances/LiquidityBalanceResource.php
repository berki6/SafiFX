<?php

namespace App\Filament\Resources\LiquidityBalances;

use App\Filament\Resources\LiquidityBalances\Pages\CreateLiquidityBalance;
use App\Filament\Resources\LiquidityBalances\Pages\EditLiquidityBalance;
use App\Filament\Resources\LiquidityBalances\Pages\ListLiquidityBalances;
use App\Filament\Resources\LiquidityBalances\Schemas\LiquidityBalanceForm;
use App\Filament\Resources\LiquidityBalances\Tables\LiquidityBalancesTable;
use App\Models\LiquidityBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LiquidityBalanceResource extends Resource
{
    protected static ?string $model = LiquidityBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return LiquidityBalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LiquidityBalancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLiquidityBalances::route('/'),
            'create' => CreateLiquidityBalance::route('/create'),
            'edit' => EditLiquidityBalance::route('/{record}/edit'),
        ];
    }
}
