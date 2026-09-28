<?php

namespace App\Filament\Resources\LiquidityBalances\Pages;

use App\Filament\Resources\LiquidityBalances\LiquidityBalanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLiquidityBalance extends EditRecord
{
    protected static string $resource = LiquidityBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
