<?php

use Livewire\Component;

new class extends Component {
    public string $query = '';
    public ?array $transaction = null;
    public bool $searched = false;

    public function mount(): void
    {
        $id = request()->query('id', '');
        if (! empty($id)) {
            $this->query = trim($id);
            $this->search();
        }
    }

    public function search(): void
    {
        $this->searched = true;
        $id = strtoupper(trim($this->query));

        if (empty($id)) {
            $this->transaction = null;
            return;
        }

        // Demo transaction data lookup
        $this->transaction = [
            'id' => $id,
            'status' => 'Payment Verified',
            'sender' => 'John Doe (+254 712 345678)',
            'recipient' => 'Jane Smith (+256 770 123456)',
            'network' => 'MTN Mobile Money',
            'sent' => '10,000.00 KES',
            'payout' => '283,500.00 UGX',
            'code' => 'QKH89210XZ',
            'date' => now()->format('M d, Y - H:i'),
            'timeline' => [
                ['title' => 'Payment Submitted', 'desc' => 'Transaction code QKH89210XZ submitted.', 'done' => true],
                ['title' => 'Payment Verified', 'desc' => 'M-PESA deposit confirmed by SafiFX operator.', 'done' => true],
                ['title' => 'Payout Processing', 'desc' => 'Payout queued to MTN Mobile Money Uganda.', 'done' => false],
                ['title' => 'Completed', 'desc' => 'Recipient account credited.', 'done' => false],
            ],
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
                    <flux:label>Transaction ID</flux:label>
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
                    <span class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-400">{{ $transaction['status'] }}</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $transaction['id'] }}</h3>
                </div>
                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ $transaction['date'] }}</span>
            </div>

            <!-- Summary Breakdown -->
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2.5 dark:border-zinc-800 dark:bg-zinc-950/50">
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Sender</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction['sender'] }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Recipient</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction['recipient'] }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Mobile Money Network</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $transaction['network'] }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Deposit Code</span>
                    <span class="font-mono font-medium text-slate-900 dark:text-white">{{ $transaction['code'] }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-white border-t border-slate-200 pt-2 dark:border-zinc-800">
                    <span>Destination Payout</span>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ $transaction['payout'] }}</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="mt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Transfer Timeline</h4>
                <ol class="mt-4 space-y-4">
                    @foreach ($transaction['timeline'] as $step)
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
