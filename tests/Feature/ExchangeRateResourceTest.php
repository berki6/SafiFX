<?php

use App\Enums\UserRole;
use App\Filament\Resources\ExchangeRates\Pages\CreateExchangeRate;
use App\Models\ExchangeRate;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));
});

test('a valid exchange rate can be created', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 28.00,
            'fee' => 200,
            'min_amount' => 1000,
            'max_amount' => 500000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ExchangeRate::where('from_currency', 'KES')->where('to_currency', 'UGX')->exists())->toBeTrue();
});

test('currency codes are normalized to uppercase regardless of how they were typed', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'kes',
            'to_currency' => 'ugx',
            'rate' => 28.00,
            'fee' => 200,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ExchangeRate::where('from_currency', 'KES')->where('to_currency', 'UGX')->exists())->toBeTrue();
});

test('a corridor cannot send a currency to itself', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'KES',
            'rate' => 1,
            'fee' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['to_currency']);
});

test('a duplicate corridor is rejected instead of creating a second rate for the same pair', function () {
    ExchangeRate::factory()->forCorridor('KES', 'UGX')->create();

    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 30,
            'fee' => 100,
        ])
        ->call('create')
        ->assertHasFormErrors(['to_currency' => 'unique']);
});

test('the customer rate must be greater than zero', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 0,
            'fee' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['rate' => 'gt']);
});

test('the fee cannot be negative', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 28,
            'fee' => -50,
        ])
        ->call('create')
        ->assertHasFormErrors(['fee' => 'gte']);
});

test('the maximum amount cannot be lower than the minimum amount', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 28,
            'fee' => 0,
            'min_amount' => 500000,
            'max_amount' => 1000,
        ])
        ->call('create')
        ->assertHasFormErrors(['max_amount']);
});

test('an operator is forbidden from creating exchange rates', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]));

    $this->get('/admin/exchange-rates/create')->assertForbidden();
});
