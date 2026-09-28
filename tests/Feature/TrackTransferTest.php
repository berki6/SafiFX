<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('track-search:127.0.0.1');
});

test('an empty search is rejected with a friendly message instead of a blank lookup', function () {
    Livewire::test('pages::⚡track')
        ->set('query', '')
        ->call('search')
        ->assertHasErrors(['query'])
        ->assertSet('searched', false);
});

test('a customer can find their transaction by reference', function () {
    $transaction = Transaction::factory()->create(['reference' => 'SFX-20260928-00124']);

    Livewire::test('pages::⚡track')
        ->set('query', 'sfx-20260928-00124')
        ->call('search')
        ->assertSet('transaction.id', $transaction->id)
        ->assertSee($transaction->status->label());
});

test('an unknown reference shows a not-found state instead of fabricated data', function () {
    Livewire::test('pages::⚡track')
        ->set('query', 'SFX-DOES-NOT-EXIST')
        ->call('search')
        ->assertSet('transaction', null)
        ->assertSee('Transaction Not Found');
});

test('a completed transaction shows the full timeline as done', function () {
    Transaction::factory()->completed()->create(['reference' => 'SFX-20260928-00999']);

    Livewire::test('pages::⚡track')
        ->set('query', 'SFX-20260928-00999')
        ->call('search')
        ->assertSee('Completed');
});

test('repeated lookups from the same connection are rate limited', function () {
    $component = Livewire::test('pages::⚡track');

    foreach (range(1, 20) as $attempt) {
        $component->set('query', "SFX-ATTEMPT-{$attempt}")->call('search');
    }

    $component->set('query', 'SFX-ONE-TOO-MANY')
        ->call('search')
        ->assertHasErrors(['query']);
});
