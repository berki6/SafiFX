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

test('an out-of-range amount shows a live warning and disables Continue, without needing to submit', function () {
    // Regression: the input's native HTML min/max attributes let the browser
    // silently block the submit before proceedToTransfer() (and its friendly
    // custom error) ever ran — no request reached the server, no message
    // ever showed. This checks the same live, submit-independent warning
    // /send already has, driven by amountOutOfRange rather than a form error.
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 500)
        ->assertSet('amountOutOfRange', true)
        ->assertSee('is outside the allowed range');
});

test('an in-range amount shows no warning and Continue stays enabled', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->assertSet('amountOutOfRange', false)
        ->assertDontSee('is outside the allowed range');
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

test('clearing the amount field does not crash the calculator', function () {
    // Regression: amount was a strictly-typed `float` property bound via
    // wire:model.live to a type="number" input. Clearing that input sends an
    // empty string, and PHP throws an uncaught TypeError assigning "" to a
    // float property — a real production 500 a user hit mid-calculation.
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->set('amount', '')
        ->assertSet('recipientAmount', 0.0)
        ->assertSet('totalPay', 200.0); // just the flat fee: totalFor(0) = 0 + fee
});

test('submitting with a cleared amount shows a friendly error instead of crashing', function () {
    Livewire::test('pages::⚡calculator')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', '')
        ->call('proceedToTransfer')
        ->assertHasErrors(['amount' => 'required'])
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
