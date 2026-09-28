<?php

use App\Enums\UserRole;
use App\Filament\Resources\LiquidityBalances\Pages\CreateLiquidityBalance;
use App\Models\LiquidityBalance;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));
});

test('a valid liquidity balance can be created with a normalized currency code', function () {
    Livewire::test(CreateLiquidityBalance::class)
        ->fillForm([
            'currency' => 'ugx',
            'available_amount' => 1000000,
            'low_threshold' => 200000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(LiquidityBalance::where('currency', 'UGX')->exists())->toBeTrue();
});

test('the available amount cannot be negative', function () {
    Livewire::test(CreateLiquidityBalance::class)
        ->fillForm([
            'currency' => 'UGX',
            'available_amount' => -500,
        ])
        ->call('create')
        ->assertHasFormErrors(['available_amount' => 'gte']);
});

test('the low threshold cannot be negative', function () {
    Livewire::test(CreateLiquidityBalance::class)
        ->fillForm([
            'currency' => 'UGX',
            'available_amount' => 1000,
            'low_threshold' => -1,
        ])
        ->call('create')
        ->assertHasFormErrors(['low_threshold' => 'gte']);
});
