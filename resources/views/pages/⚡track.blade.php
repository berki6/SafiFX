<?php

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {
    public string $query = '';
    public ?Transaction $transaction = null;
    public bool $searched = false;

    public function mount(): void
    {
        $id = request()->query('id', '');

        if (empty($id)) {
            return;
        }

        $this->query = trim($id);

        // Only auto-run the lookup for something that could plausibly be a real
        // reference; a too-short id (e.g. a mistyped link) is left in the field
        // for the user to correct instead of throwing a validation exception
        // during mount.
        if (strlen($this->query) >= 3) {
            $this->search();
        }
    }

    public function search(): void
    {
        $this->validate([
            'query' => 'required|string|min:3|max:50',
        ], [
            'query.required' => 'Please enter a transaction reference to search for.',
            'query.min' => 'That reference looks too short — please check it and try again.',
        ]);

        $rateLimitKey = 'track-search:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 20)) {
            $this->addError('query', 'Too many lookups from this connection. Please wait '.RateLimiter::availableIn($rateLimitKey).' seconds and try again.');

            return;
        }

        RateLimiter::hit($rateLimitKey, decaySeconds: 60);

        $this->searched = true;
        $reference = strtoupper(trim($this->query));

        $this->transaction = Transaction::query()->where('reference', $reference)->first();
    }

    /**
     * @return array<int, array{title: string, desc: string, done: bool}>
     */
    public function getTimelineProperty(): array
    {
        if (! $this->transaction) {
            return [];
        }

        $status = $this->transaction->status;
        $failed = in_array($status, [TransactionStatus::Failed, TransactionStatus::Cancelled, TransactionStatus::Refunded], true);

        $reachedVerified = in_array($status, [TransactionStatus::PaymentVerified, TransactionStatus::PayoutProcessing, TransactionStatus::Completed], true);
        $reachedProcessing = in_array($status, [TransactionStatus::PayoutProcessing, TransactionStatus::Completed], true);
        $reachedCompleted = $status === TransactionStatus::Completed;

        return [
            ['title' => 'Payment Submitted', 'desc' => "Transaction code {$this->transaction->payment_reference} submitted.", 'done' => true],
            ['title' => 'Payment Verified', 'desc' => $failed ? 'This transfer did not proceed past payment verification.' : 'Deposit confirmed by a SafiFX operator.', 'done' => $reachedVerified],
            ['title' => 'Payout Processing', 'desc' => "Payout queued to {$this->transaction->recipient_network}.", 'done' => $reachedProcessing],
            ['title' => 'Completed', 'desc' => 'Recipient account credited.', 'done' => $reachedCompleted],
        ];
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <!-- Lookup Form -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
        <div class="border-b border-slate-100 pb-4 dark:border-zinc-800">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                <span>01</span><span>&bull;</span><span>Track Your Transfer</span>
            </div>
            <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Check transfer status</h2>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Enter your SafiFX transaction reference number to view real-time progress.</p>
        </div>

        <form wire:submit="search" class="mt-6 flex flex-col gap-3 sm:flex-row items-end">
            <div class="w-full">
                <flux:field>
                    <flux:label>Transaction ID <span class="text-rose-500">*</span></flux:label>
                    <flux:input
                        wire:model="query"
                        icon="magnifying-glass"
                        placeholder="e.g. SFX-20260928-12345"
                        required
                    />
                </flux:field>
            </div>
            <flux:button type="submit" variant="primary" icon="arrow-right" class="w-full sm:w-auto shrink-0 justify-center">
                Check Status
            </flux:button>
        </form>
    </div>

    <!-- Results Section -->
    @if ($searched && $transaction)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-zinc-800">
                <div>
                    <span class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-400">{{ $transaction->status->label() }}</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $transaction->reference }}</h3>
                </div>
                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ $transaction->created_at->format('M d, Y - H:i') }}</span>
            </div>

            <!-- Summary Breakdown -->
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2.5 dark:border-zinc-800 dark:bg-zinc-950/50">
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Sender</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction->customer_name }} ({{ $transaction->customer_phone }})</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Recipient</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction->recipient_name }} ({{ $transaction->recipient_phone }})</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Mobile Money Network</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction->recipient_network }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Deposit Code</span>
                    <span class="font-mono font-medium text-slate-900 dark:text-white">{{ $transaction->payment_reference }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-white border-t border-slate-200 pt-2 dark:border-zinc-800">
                    <span>Destination Payout</span>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ number_format((float) $transaction->recipient_amount, 2) }} {{ $transaction->to_currency }}</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="mt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Transfer Timeline</h4>
                <ol class="mt-4 space-y-4">
                    @foreach ($this->timeline as $step)
                        <li class="flex items-start gap-3">
                            @if ($step['done'])
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <flux:icon.check class="size-3.5" />
                                </span>
                            @else
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-400 dark:border-zinc-700 dark:text-zinc-600">
                                    <flux:icon.clock class="size-3.5" />
                                </span>
                            @endif
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $step['title'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-zinc-400">{{ $step['desc'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    @elseif ($searched)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
            <flux:icon.exclamation-circle class="mx-auto size-8 text-rose-500" />
            <h3 class="mt-2 text-base font-bold text-slate-900 dark:text-white">Transaction Not Found</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">No transfer matches the reference number <span class="font-mono font-bold">{{ $query }}</span>. Please check the ID and try again.</p>
        </div>
    @endif
</div>
