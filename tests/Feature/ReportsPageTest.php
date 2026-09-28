<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('an admin can see transactions in the reports table', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    $completed = Transaction::factory()->completed()->create();
    $submitted = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    Livewire::test(Reports::class)
        ->assertCanSeeTableRecords([$completed, $submitted]);
});

test('the status filter narrows the reports table to matching transactions', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    $completed = Transaction::factory()->completed()->create();
    $submitted = Transaction::factory()->create(['status' => TransactionStatus::PaymentSubmitted]);

    Livewire::test(Reports::class)
        ->filterTable('status', TransactionStatus::Completed->value)
        ->assertCanSeeTableRecords([$completed])
        ->assertCanNotSeeTableRecords([$submitted]);
});

test('exporting the report downloads a csv with the filtered transactions', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => UserRole::SuperAdmin]));

    $transaction = Transaction::factory()->create([
        'reference' => 'SFX-20260928-00001',
        'status' => TransactionStatus::PaymentSubmitted,
    ]);

    Livewire::test(Reports::class)
        ->callTableAction('export')
        ->assertFileDownloaded('safifx-transactions-'.now()->format('Y-m-d').'.csv');
});
