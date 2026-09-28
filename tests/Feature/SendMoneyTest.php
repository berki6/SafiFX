<?php

use App\Enums\TransactionStatus;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\MobileMoneyNetwork;
use App\Models\Transaction;
use App\Notifications\TransactionCreatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('send-money:127.0.0.1');
});

beforeEach(function () {
    $kenya = Country::factory()->create([
        'name' => 'Kenya',
        'code' => 'KE',
        'currency_code' => 'KES',
        'receiving_network' => 'M-Pesa Paybill',
        'receiving_number' => '522522',
        'receiving_account_name' => 'SafiFX Kenya Ltd',
    ]);

    $uganda = Country::factory()->create([
        'name' => 'Uganda',
        'code' => 'UG',
        'currency_code' => 'UGX',
        'receiving_network' => 'MTN / Airtel Merchant',
        'receiving_number' => '+256 770 123456',
        'receiving_account_name' => 'SafiFX Uganda',
    ]);

    MobileMoneyNetwork::factory()->create(['country_id' => $uganda->id, 'name' => 'MTN Mobile Money']);

    ExchangeRate::factory()->forCorridor('KES', 'UGX')->create([
        'rate' => 28.00,
        'fee' => 200,
    ]);
});

test('the send-money page renders over a real HTTP request', function () {
    // Regression: full-page Livewire routes render inside the app's default layout
    // (layouts::app, the staff sidebar shell), which calls route('dashboard') — a
    // route that doesn't exist for customers. Component-only tests never hit that
    // layout at all, so this page 500'd in production despite every test above
    // passing. /send and /track must use layouts::public instead.
    $this->get('/send')->assertOk();
});

test('submitting a valid transfer creates a transaction with the snapshotted rate and fee, and emails the customer', function () {
    Notification::fake();

    Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'john.doe@example.com')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+256770123456')
        ->set('network', 'MTN Mobile Money')
        ->set('transactionCode', 'QKH89210XZ')
        ->call('submitTransfer')
        ->assertSet('submitted', true);

    $transaction = Transaction::sole();

    expect($transaction->status)->toBe(TransactionStatus::PaymentSubmitted);
    expect((float) $transaction->amount_sent)->toBe(10000.0);
    expect((float) $transaction->exchange_rate)->toBe(28.0);
    expect((float) $transaction->fee)->toBe(200.0);
    expect((float) $transaction->total_paid)->toBe(10200.0);
    expect((float) $transaction->recipient_amount)->toBe(280000.0);
    expect($transaction->payment_reference)->toBe('QKH89210XZ');
    expect($transaction->reference)->toStartWith('SFX-');
    expect($transaction->customer_email)->toBe('john.doe@example.com');

    Notification::assertSentOnDemand(
        TransactionCreatedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'john.doe@example.com',
    );
});

test('the transfer form requires all fields before submitting', function () {
    Livewire::test('pages::⚡send-money')
        ->call('submitTransfer')
        ->assertHasErrors(['senderName', 'senderEmail', 'recipientName', 'transactionCode']);

    expect(Transaction::count())->toBe(0);
});

test('an implausible email address is rejected', function () {
    Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'not-an-email')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+256770123456')
        ->set('network', 'MTN Mobile Money')
        ->set('transactionCode', 'QKH89210XZ')
        ->call('submitTransfer')
        ->assertHasErrors(['senderEmail']);

    expect(Transaction::count())->toBe(0);
});

test('an amount carried in via the url below the corridor minimum is rejected on submit', function () {
    // The calculator enforces limits client-side, but the amount arrives here via
    // a query string the customer could edit by hand, so it must be re-checked.
    Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 500) // below the factory's default min_amount of 1000
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'john.doe@example.com')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+256770123456')
        ->set('network', 'MTN Mobile Money')
        ->set('transactionCode', 'QKH89210XZ')
        ->call('submitTransfer')
        ->assertHasErrors(['amount'])
        ->assertSet('submitted', false);

    expect(Transaction::count())->toBe(0);
});

test('an amount above the corridor maximum is rejected on submit', function () {
    Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 600000) // above the factory's default max_amount of 500000
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'john.doe@example.com')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+256770123456')
        ->set('network', 'MTN Mobile Money')
        ->set('transactionCode', 'QKH89210XZ')
        ->call('submitTransfer')
        ->assertHasErrors(['amount']);

    expect(Transaction::count())->toBe(0);
});

test('sending and receiving in the same currency is rejected', function () {
    Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'KES')
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'john.doe@example.com')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+254700000000')
        ->set('network', 'M-PESA')
        ->set('transactionCode', 'QKH89210XZ')
        ->call('submitTransfer')
        ->assertHasErrors(['network']);

    expect(Transaction::count())->toBe(0);
});

test('repeated transfer submissions from the same connection are rate limited', function () {
    $component = Livewire::test('pages::⚡send-money')
        ->set('fromCurrency', 'KES')
        ->set('toCurrency', 'UGX')
        ->set('amount', 10000)
        ->set('senderName', 'John Doe')
        ->set('senderPhone', '+254712345678')
        ->set('senderEmail', 'john.doe@example.com')
        ->set('recipientName', 'Jane Smith')
        ->set('recipientPhone', '+256770123456')
        ->set('network', 'MTN Mobile Money');

    foreach (range(1, 5) as $attempt) {
        $component->set('transactionCode', "CODE{$attempt}XYZ")->call('submitTransfer');
    }

    expect(Transaction::count())->toBe(5);

    $component->set('transactionCode', 'ONETOOMANYXYZ')
        ->call('submitTransfer')
        ->assertHasErrors(['transactionCode']);

    expect(Transaction::count())->toBe(5);
});
