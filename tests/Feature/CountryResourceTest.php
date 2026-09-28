<?php

use App\Enums\UserRole;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Models\Country;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));
});

test('country codes are normalized to uppercase regardless of how they were typed', function () {
    Livewire::test(CreateCountry::class)
        ->fillForm([
            'name' => 'Kenya',
            'code' => 'ke',
            'currency_code' => 'kes',
            'receiving_network' => 'M-Pesa Paybill',
            'receiving_number' => '522522',
            'receiving_account_name' => 'SafiFX Kenya Ltd',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Country::where('code', 'KE')->where('currency_code', 'KES')->exists())->toBeTrue();
});

test('the iso code must be exactly 2 letters', function () {
    Livewire::test(CreateCountry::class)
        ->fillForm([
            'name' => 'Kenya',
            'code' => 'KEN',
            'currency_code' => 'KES',
            'receiving_network' => 'M-Pesa Paybill',
            'receiving_number' => '522522',
            'receiving_account_name' => 'SafiFX Kenya Ltd',
        ])
        ->call('create')
        ->assertHasFormErrors(['code']);
});

test('the currency code must be exactly 3 letters', function () {
    Livewire::test(CreateCountry::class)
        ->fillForm([
            'name' => 'Kenya',
            'code' => 'KE',
            'currency_code' => 'KE',
            'receiving_network' => 'M-Pesa Paybill',
            'receiving_number' => '522522',
            'receiving_account_name' => 'SafiFX Kenya Ltd',
        ])
        ->call('create')
        ->assertHasFormErrors(['currency_code']);
});

test('an operator is forbidden from creating countries', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]));

    $this->get('/admin/countries/create')->assertForbidden();
});
