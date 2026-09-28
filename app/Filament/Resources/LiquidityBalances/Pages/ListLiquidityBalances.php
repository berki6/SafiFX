<?php

namespace App\Filament\Resources\LiquidityBalances\Pages;

use App\Filament\Resources\LiquidityBalances\LiquidityBalanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiquidityBalances extends ListRecords
{
    protected static string $resource = LiquidityBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
