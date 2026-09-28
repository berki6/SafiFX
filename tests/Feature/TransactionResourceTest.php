<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Widgets\TodayCurrencyBreakdown;
use App\Filament\Widgets\TodayOverview;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\TransactionCompletedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('a super admin can confirm a submitted payment', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]);
    $transaction = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    $this->actingAs($admin);

    Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->callAction('confirmPayment');

    $transaction->refresh();

    expect($transaction->status)->toBe(TransactionStatus::PaymentVerified);
    expect($transaction->payment_verified_at)->not->toBeNull();
    expect($transaction->processed_by_id)->toBe($admin->id);

    Notification::assertSentOnDemand(
        PaymentVerifiedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $transaction->customer_email,
    );
});

test('a super admin can reject a submitted payment', function () {
    $transaction = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->callAction('rejectPayment');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Failed);
});

test('an operator cannot confirm or reject a payment', function () {
    $transaction = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::Operator]));

    Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->assertActionHidden('confirmPayment')
        ->assertActionHidden('rejectPayment');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaymentSubmitted);
});

test('both a super admin and an operator can mark a verified transaction as paid', function (UserRole $role) {
    Notification::fake();

    $user = User::factory()->create(['is_admin' => true, 'role' => $role]);
    $transaction = Transaction::factory()->verified()->create();

    $this->actingAs($user);

    Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->callAction('markAsPaid', data: ['payout_reference' => 'PO123456']);

    $transaction->refresh();

    expect($transaction->status)->toBe(TransactionStatus::Completed);
    expect($transaction->payout_reference)->toBe('PO123456');
    expect($transaction->completed_at)->not->toBeNull();
    expect($transaction->processed_by_id)->toBe($user->id);

    Notification::assertSentOnDemand(
        TransactionCompletedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $transaction->customer_email,
    );
})->with([UserRole::SuperAdmin, UserRole::Operator]);

test('mark as paid is hidden until the payment has been verified', function () {
    $transaction = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->assertActionHidden('markAsPaid');
});

test('the transactions list tabs scope to the right statuses', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    $awaitingVerification = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);
    $processing = Transaction::factory()->verified()->create();
    $completed = Transaction::factory()->completed()->create();
    $failed = Transaction::factory()->create(['status' => TransactionStatus::Failed]);

    Livewire::test(ListTransactions::class)
        ->set('activeTab', 'awaiting_verification')
        ->assertCanSeeTableRecords([$awaitingVerification])
        ->assertCanNotSeeTableRecords([$processing, $completed, $failed]);

    Livewire::test(ListTransactions::class)
        ->set('activeTab', 'processing')
        ->assertCanSeeTableRecords([$processing])
        ->assertCanNotSeeTableRecords([$awaitingVerification, $completed, $failed]);

    Livewire::test(ListTransactions::class)
        ->set('activeTab', 'completed')
        ->assertCanSeeTableRecords([$completed])
        ->assertCanNotSeeTableRecords([$awaitingVerification, $processing, $failed]);

    Livewire::test(ListTransactions::class)
        ->set('activeTab', 'failed')
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$awaitingVerification, $processing, $completed]);
});

test('confirming payment is a no-op if another admin already processed it first', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]);
    $transaction = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    $this->actingAs($admin);

    // Open the confirmation modal while the record still says "Payment Submitted" (so
    // mounting succeeds), then have another admin process it out-of-band before the
    // "Yes, confirm" click lands inside that already-open modal — the exact race the
    // before()/halt() guard exists to catch, since Livewire only re-checks ->visible()
    // at mount time, not again when the mounted action is actually called.
    $component = Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->mountAction('confirmPayment');

    Transaction::whereKey($transaction->id)->update(['status' => TransactionStatus::Failed]);

    $component->callMountedAction()->assertActionHalted('confirmPayment');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Failed);
    expect($transaction->fresh()->processed_by_id)->toBeNull();
});

test('marking as paid is a no-op if the payment was rejected out from under the page', function () {
    $admin = User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]);
    $transaction = Transaction::factory()->verified()->create();

    $this->actingAs($admin);

    $component = Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
        ->mountAction('markAsPaid');

    Transaction::whereKey($transaction->id)->update(['status' => TransactionStatus::Failed]);

    $component->setActionData(['payout_reference' => 'PO123456'])
        ->callMountedAction()
        ->assertActionHalted('markAsPaid');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Failed);
    expect($transaction->fresh()->payout_reference)->toBeNull();
});

test('the today overview widget renders for an admin', function () {
    Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);
    Transaction::factory()->completed()->create();

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    Livewire::test(TodayOverview::class)->assertOk();
    Livewire::test(TodayCurrencyBreakdown::class)->assertOk();
});

test('a completed transaction is reflected in the currency breakdown regardless of which direction the corridor runs', function () {
    // Regression: the dashboard used to hard-filter every money stat to
    // from_currency = 'KES', so a real completed transaction going the other
    // way (e.g. UGX -> KES, same as KES -> UGX is just as valid a corridor)
    // silently showed as 0.00 everywhere despite genuinely happening today.
    Transaction::factory()->completed()->create([
        'from_currency' => 'UGX',
        'to_currency' => 'KES',
        'amount_sent' => 100000,
        'exchange_rate' => 0.0357,
        'market_rate' => 0.036,
        'fee' => 1600,
        'total_paid' => 101600,
        'recipient_amount' => 3570,
    ]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    Livewire::test(TodayCurrencyBreakdown::class)
        ->assertOk()
        ->assertSee('UGX')
        ->assertSee('KES')
        ->assertSee('101,600.00') // Received (UGX)
        ->assertSee('3,570.00') // Paid Out (KES)
        ->assertSee('1,600.00'); // Fees (UGX)
});
