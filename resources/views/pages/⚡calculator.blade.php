<?php

use Livewire\Component;

new class extends Component {
    public string $fromCurrency = 'KES';
    public string $toCurrency = 'UGX';
    public float $amount = 10000;

    public array $currencies = [
        'KES' => ['name' => 'Kenya (KES)', 'symbol' => 'KSh', 'phone_prefix' => '+254', 'flag' => 'KE'],
        'UGX' => ['name' => 'Uganda (UGX)', 'symbol' => 'USh', 'phone_prefix' => '+256', 'flag' => 'UG'],
        'TZS' => ['name' => 'Tanzania (TZS)', 'symbol' => 'TSh', 'phone_prefix' => '+255', 'flag' => 'TZ'],
        'RWF' => ['name' => 'Rwanda (RWF)', 'symbol' => 'FRw', 'phone_prefix' => '+250', 'flag' => 'RW'],
        'USD' => ['name' => 'United States (USD)', 'symbol' => '$', 'phone_prefix' => '+1', 'flag' => 'US'],
    ];

    public array $rates = [
        'KES-UGX' => 28.5,
        'UGX-KES' => 0.035,
        'KES-TZS' => 19.2,
        'TZS-KES' => 0.052,
        'KES-RWF' => 10.1,
        'RWF-KES' => 0.099,
        'USD-KES' => 129.50,
        'KES-USD' => 0.0077,
    ];

    public function swapCurrencies(): void
    {
        $temp = $this->fromCurrency;
        $this->fromCurrency = $this->toCurrency;
        $this->toCurrency = $temp;
    }

    public function getRateProperty(): float
    {
        $key = "{$this->fromCurrency}-{$this->toCurrency}";
        return $this->rates[$key] ?? 1.0;
    }

    public function getFeeProperty(): float
    {
        return round($this->amount * 0.005, 2);
    }

    public function getRecipientAmountProperty(): float
    {
        $netAmount = max(0, $this->amount - $this->fee);
        return round($netAmount * $this->rate, 2);
    }

    public function proceedToTransfer(): void
    {
        $params = http_build_query([
            'from' => $this->fromCurrency,
            'to' => $this->toCurrency,
            'amount' => $this->amount,
        ]);

        $this->redirect("/send?{$params}", navigate: true);
    }
};
?>

<div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
    <div class="border-b border-slate-100 pb-4 dark:border-zinc-800">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
            <span>01</span><span>&bull;</span><span>Instant Currency Calculator</span>
        </div>
        <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Calculate your transfer</h2>
        <p class="text-xs text-slate-500 dark:text-zinc-400">Zero hidden fees. Guaranteed exchange rates updated live.</p>
    </div>

    <form wire:submit="proceedToTransfer" class="mt-6 space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 items-end">
            <!-- From Currency -->
            <flux:field>
                <flux:label>You send from</flux:label>
                <flux:select wire:model.live="fromCurrency" icon="banknotes">
                    @foreach ($currencies as $code => $info)
                        <option value="{{ $code }}">{{ $info['name'] }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <!-- Swap Button & To Currency -->
            <div class="grid grid-cols-[1fr_auto] items-end gap-2">
                <flux:field>
                    <flux:label>Recipient gets</flux:label>
                    <flux:select wire:model.live="toCurrency" icon="globe-alt">
                        @foreach ($currencies as $code => $info)
                            @if ($code !== $fromCurrency)
                                <option value="{{ $code }}">{{ $info['name'] }}</option>
                            @endif
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:button 
                    type="button" 
                    wire:click="swapCurrencies" 
                    icon="arrows-right-left" 
                    variant="subtle"
                    square
                    aria-label="Swap currencies" 
                    class="mb-0.5 border border-slate-200 dark:border-zinc-700" 
                />
            </div>
        </div>

        <!-- Amount Input -->
        <flux:field>
            <flux:label>Amount to send ({{ $fromCurrency }})</flux:label>
            <flux:input 
                type="number" 
                wire:model.live.debounce.300ms="amount" 
                icon="currency-dollar" 
                min="100" 
                step="100" 
                required 
            />
            <flux:error name="amount" />
        </flux:field>

        <!-- Calculation Summary -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-2 dark:border-zinc-800 dark:bg-zinc-950/50">
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                <span>Exchange Rate</span>
                <span class="font-medium text-slate-900 dark:text-white">1 {{ $fromCurrency }} = {{ number_format($this->rate, 4) }} {{ $toCurrency }}</span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                <span>Transfer Fee (0.5%)</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ number_format($this->fee, 2) }} {{ $fromCurrency }}</span>
            </div>
            <div class="border-t border-slate-200/60 pt-2 dark:border-zinc-800 flex items-center justify-between text-sm font-bold text-slate-900 dark:text-white">
                <span>Recipient Receives</span>
                <span class="text-base text-emerald-600 dark:text-emerald-400">{{ number_format($this->recipientAmount, 2) }} {{ $toCurrency }}</span>
            </div>
        </div>

        <!-- Continue Action -->
        <flux:button 
            type="submit" 
            variant="primary" 
            icon="arrow-right"
            icon-position="after"
            class="w-full justify-center text-base font-bold"
        >
            Continue to Transfer
        </flux:button>
    </form>
</div>
