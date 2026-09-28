<?php

use App\Models\Country;
use App\Models\ExchangeRate;
use Livewire\Component;

new class extends Component {
    public string $fromCurrency = 'KES';
    public string $toCurrency = 'UGX';

    /**
     * A string, not float, deliberately: this is wire:model.live-bound to a
     * type="number" input, and clearing that input sends an empty string.
     * Assigning "" to a strictly-typed `float` property throws a TypeError
     * (uncaught, so it 500s) before validation ever gets a chance to show
     * the friendly "Please enter an amount" message. A string property can
     * hold "" without complaint; amountValue below does the numeric cast.
     */
    public string $amount = '10000';

    /**
     * @return \Illuminate\Support\Collection<int, Country>
     */
    public function getCountriesProperty()
    {
        return Country::active()->orderBy('name')->get();
    }

    public function getRateModelProperty(): ?ExchangeRate
    {
        return ExchangeRate::forCorridor($this->fromCurrency, $this->toCurrency)->first();
    }

    public function getRateProperty(): float
    {
        return (float) ($this->rateModel?->rate ?? 0);
    }

    public function getFeeProperty(): float
    {
        return (float) ($this->rateModel?->fee ?? 0);
    }

    public function getAmountValueProperty(): float
    {
        return is_numeric($this->amount) ? (float) $this->amount : 0.0;
    }

    public function getTotalPayProperty(): float
    {
        return $this->rateModel?->totalFor($this->amountValue) ?? $this->amountValue;
    }

    public function getRecipientAmountProperty(): float
    {
        return $this->rateModel?->recipientAmountFor($this->amountValue) ?? 0.0;
    }

    public function swapCurrencies(): void
    {
        $temp = $this->fromCurrency;
        $this->fromCurrency = $this->toCurrency;
        $this->toCurrency = $temp;
    }

    public function proceedToTransfer(): void
    {
        if ($this->fromCurrency === $this->toCurrency) {
            $this->addError('amount', 'Please choose two different currencies.');

            return;
        }

        $rate = $this->rateModel;

        if (! $rate) {
            $this->addError('amount', 'SafiFX doesn\'t support this currency pair yet — please choose a different pair.');

            return;
        }

        $this->validate([
            'amount' => [
                'required',
                'numeric',
                'min:'.($rate->min_amount ?? 1),
                ...($rate->max_amount ? ['max:'.$rate->max_amount] : []),
            ],
        ], [
            'amount.required' => 'Please enter an amount to send.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => "The minimum transfer for {$this->fromCurrency} → {$this->toCurrency} is :min {$this->fromCurrency}.",
            'amount.max' => "The maximum transfer for {$this->fromCurrency} → {$this->toCurrency} is :max {$this->fromCurrency}.",
        ]);

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
                    @foreach ($this->countries as $country)
                        <option value="{{ $country->currency_code }}">{{ $country->flag_emoji }} {{ $country->name }} ({{ $country->currency_code }})</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <!-- Swap Button & To Currency -->
            <div class="grid grid-cols-[1fr_auto] items-end gap-2">
                <flux:field>
                    <flux:label>Recipient gets</flux:label>
                    <flux:select wire:model.live="toCurrency" icon="globe-alt">
                        @foreach ($this->countries as $country)
                            @if ($country->currency_code !== $fromCurrency)
                                <option value="{{ $country->currency_code }}">{{ $country->flag_emoji }} {{ $country->name }} ({{ $country->currency_code }})</option>
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
            <flux:label>Amount to send ({{ $fromCurrency }}) <span class="text-rose-500">*</span></flux:label>
            <flux:input
                type="number"
                wire:model.live.debounce.300ms="amount"
                icon="currency-dollar"
                min="{{ $this->rateModel?->min_amount ?? 100 }}"
                :max="$this->rateModel?->max_amount"
                step="100"
                required
            />
            <flux:error name="amount" />
        </flux:field>

        @if (! $this->rateModel)
            <p class="text-xs font-medium text-rose-600 dark:text-rose-400">This corridor isn't available yet — please choose a different pair.</p>
        @else
            <!-- Calculation Summary -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-2 dark:border-zinc-800 dark:bg-zinc-950/50">
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Exchange Rate</span>
                    <span class="font-medium text-slate-900 dark:text-white">1 {{ $fromCurrency }} = {{ number_format($this->rate, 4) }} {{ $toCurrency }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>SafiFX Fee</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ number_format($this->fee, 2) }} {{ $fromCurrency }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Total You Pay</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ number_format($this->totalPay, 2) }} {{ $fromCurrency }}</span>
                </div>
                <div class="border-t border-slate-200/60 pt-2 dark:border-zinc-800 flex items-center justify-between text-sm font-bold text-slate-900 dark:text-white">
                    <span>Recipient Receives</span>
                    <span class="text-base text-emerald-600 dark:text-emerald-400">{{ number_format($this->recipientAmount, 2) }} {{ $toCurrency }}</span>
                </div>
            </div>
        @endif

        <!-- Continue Action -->
        <flux:button
            type="submit"
            variant="primary"
            icon="arrow-right"
            icon-position="after"
            class="w-full justify-center text-base font-bold"
            :disabled="! $this->rateModel"
        >
            Continue to Transfer
        </flux:button>
    </form>
</div>
