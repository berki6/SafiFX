<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    /**
     * The core admin workflow from docs/SAFIFX.md §14/§15: verify the customer's
     * payment, then record the payout reference once the recipient has been paid.
     *
     * `visible()` reflects the transaction's current status; `authorize()` reflects
     * who's allowed to do it (see TransactionPolicy) — both must pass to show the button.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmPayment')
                ->label('Confirm Payment')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->modalHeading('Confirm this payment was received?')
                ->modalDescription(fn (Transaction $record) => sprintf(
                    'This confirms SafiFX received %s %s from %s (code %s) and moves the transfer to payout processing. Only confirm after verifying the deposit yourself.',
                    number_format((float) $record->total_paid, 2),
                    $record->from_currency,
                    $record->customer_name,
                    $record->payment_reference,
                ))
                ->modalSubmitActionLabel('Yes, payment received')
                ->authorize('confirmPayment')
                ->visible(fn (Transaction $record) => $record->status === TransactionStatus::PaymentSubmitted)
                ->before(function (Action $action, Transaction $record) {
                    if ($record->fresh()?->status !== TransactionStatus::PaymentSubmitted) {
                        Notification::make()
                            ->title('This transaction has already moved on')
                            ->body('Its status changed since this page loaded — refresh to see the current state.')
                            ->warning()
                            ->send();

                        $action->halt();
                    }
                })
                ->action(function (Transaction $record) {
                    try {
                        $record->update([
                            'status' => TransactionStatus::PaymentVerified,
                            'payment_verified_at' => now(),
                            'processed_by_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Payment confirmed — ready for payout')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Log::error('Failed to confirm payment', ['transaction_id' => $record->id, 'error' => $exception->getMessage()]);

                        Notification::make()
                            ->title('Action Failed')
                            ->body('An unexpected error occurred while confirming this payment. Nothing was changed.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('rejectPayment')
                ->label('Reject Payment')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->requiresConfirmation()
                ->modalHeading('Reject this payment?')
                ->modalDescription(fn (Transaction $record) => sprintf(
                    'This marks transaction %s as Failed and stops the transfer. Only reject if the mobile money deposit could not be verified — this is visible to the customer when they track their transfer.',
                    $record->reference,
                ))
                ->modalSubmitActionLabel('Yes, reject payment')
                ->authorize('rejectPayment')
                ->visible(fn (Transaction $record) => $record->status === TransactionStatus::PaymentSubmitted)
                ->before(function (Action $action, Transaction $record) {
                    if ($record->fresh()?->status !== TransactionStatus::PaymentSubmitted) {
                        Notification::make()
                            ->title('This transaction has already moved on')
                            ->body('Its status changed since this page loaded — refresh to see the current state.')
                            ->warning()
                            ->send();

                        $action->halt();
                    }
                })
                ->action(function (Transaction $record) {
                    try {
                        $record->update([
                            'status' => TransactionStatus::Failed,
                            'processed_by_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Payment rejected')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Log::error('Failed to reject payment', ['transaction_id' => $record->id, 'error' => $exception->getMessage()]);

                        Notification::make()
                            ->title('Action Failed')
                            ->body('An unexpected error occurred while rejecting this payment. Nothing was changed.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('markAsPaid')
                ->label('Mark As Paid')
                ->color('primary')
                ->icon(Heroicon::OutlinedBanknotes)
                ->modalHeading('Record the payout')
                ->modalDescription(fn (Transaction $record) => sprintf(
                    'Enter the mobile money transaction code once %s %s has actually been sent to %s (%s) on %s. This marks the transfer Completed and can\'t be undone from this screen.',
                    number_format((float) $record->recipient_amount, 2),
                    $record->to_currency,
                    $record->recipient_name,
                    $record->recipient_phone,
                    $record->recipient_network,
                ))
                ->modalSubmitActionLabel('Mark as paid')
                ->schema([
                    TextInput::make('payout_reference')
                        ->label('Payout Transaction Code')
                        ->required()
                        ->minLength(4)
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Enter the mobile money transaction code for the payout.',
                            'min' => 'That code looks too short — please double-check it.',
                        ]),
                ])
                ->authorize('markAsPaid')
                ->visible(fn (Transaction $record) => in_array($record->status, [TransactionStatus::PaymentVerified, TransactionStatus::PayoutProcessing], true))
                ->before(function (Action $action, Transaction $record) {
                    if (! in_array($record->fresh()?->status, [TransactionStatus::PaymentVerified, TransactionStatus::PayoutProcessing], true)) {
                        Notification::make()
                            ->title('This transaction has already moved on')
                            ->body('Its status changed since this page loaded — refresh to see the current state.')
                            ->warning()
                            ->send();

                        $action->halt();
                    }
                })
                ->action(function (Transaction $record, array $data) {
                    try {
                        $record->update([
                            'payout_reference' => $data['payout_reference'],
                            'status' => TransactionStatus::Completed,
                            'completed_at' => now(),
                            'processed_by_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Transaction marked as paid')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Log::error('Failed to mark transaction as paid', ['transaction_id' => $record->id, 'error' => $exception->getMessage()]);

                        Notification::make()
                            ->title('Action Failed')
                            ->body('An unexpected error occurred while recording this payout. Nothing was changed.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
