<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
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

test('the today overview widget renders for an admin', function () {
    Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);
    Transaction::factory()->completed()->create();

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    Livewire::test(TodayOverview::class)->assertOk();
});
