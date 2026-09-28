<?php

use App\Models\Country;
use App\Models\ExchangeRate;
use Livewire\Livewire;

beforeEach(function () {
    Country::factory()->create(['name' => 'Kenya', 'code' => 'KE', 'currency_code' => 'KES']);
    Country::factory()->create(['name' => 'Uganda', 'code' => 'UG', 'currency_code' => 'UGX']);

    ExchangeRate::factory()->forCorridor('KES', 'UGX')->create([
        'rate' => 28.00,
        'fee' => 200,
        'min_amount' => 1000,
        'max_amount' => 500000,
    ]);
});

test('the calculator actually renders on the homepage', function () {
    // Regression: welcome.blade.php embedded it via the <livewire:pages::⚡calculator />
    // HTML-tag syntax. Livewire's tag-name parser doesn't support the ⚡ emoji in that
    // position — it silently passed the literal tag through as dead text instead of
    // compiling it, regardless of "." vs "::". The calculator never rendered, in
    // production or locally, and every test above missed it entirely by testing the
    // component in isolation. Fixed by switching to @livewire('pages::⚡calculator'),
    // the directive form, which takes the name as a plain string with no tag-parsing
    // involved.
    $this->get('/')->assertOk()->assertSee('Calculate your transfer');
});

test('calculator uses the configured exchange rate, not a hardcoded one', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->assertSet('rate', 28.00)
        ->assertSet('fee', 200.0)
        ->assertSet('recipientAmount', 280000.0)
        ->assertSet('totalPay', 10200.0);
});

test('calculator rejects an amount below the corridor minimum', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 500)
        ->call('proceedToTransfer')
        ->assertHasErrors(['amount']);
});

test('calculator rejects an amount above the corridor maximum', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 600000)
        ->call('proceedToTransfer')
        ->assertHasErrors(['amount']);
});

test('calculator redirects to send-money with the chosen corridor and amount', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->call('proceedToTransfer')
        ->assertRedirect('/send?from=KES&to=UGX&amount=10000');
});

test('choosing the same currency for both sides is rejected', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'KES')
        ->set('amount', 10000)
        ->call('proceedToTransfer')
        ->assertHasErrors(['amount'])
        ->assertNoRedirect();
});

test('an unconfigured corridor is rejected instead of redirecting', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'UGX')
        ->set('toCurrency', 'KES') // no UGX -> KES rate seeded in this test
        ->set('amount', 10000)
        ->call('proceedToTransfer')
        ->assertHasErrors(['amount'])
        ->assertNoRedirect();
});
