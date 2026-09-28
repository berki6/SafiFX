<?php

use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\MobileMoneyNetwork;
use App\Models\Transaction;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component {
    public string $fromCurrency = 'KES';
    public string $toCurrency = 'UGX';
    public float $amount = 10000;

    public string $senderName = '';
    public string $senderPhone = '';
    public string $recipientName = '';
    public string $recipientPhone = '';
    public string $network = '';
    public string $transactionCode = '';

    public bool $submitted = false;
    public string $transactionReference = '';

    public function mount(): void
    {
        $this->fromCurrency = strtoupper((string) request()->query('from', 'KES'));
        $this->toCurrency = strtoupper((string) request()->query('to', 'UGX'));
        $this->amount = (float) request()->query('amount', 10000);

        $fromCountry = Country::query()->where('currency_code', $this->fromCurrency)->first();
        $toCountry = Country::query()->where('currency_code', $this->toCurrency)->first();

        $this->senderPhone = $fromCountry ? $this->phonePrefixFor($fromCountry) : '';
        $this->recipientPhone = $toCountry ? $this->phonePrefixFor($toCountry) : '';

        $this->network = $this->recipientNetworks->first()?->name ?? '';
    }

    private function phonePrefixFor(Country $country): string
    {
        // The receiving_number already carries a leading "+<code> " for most
        // corridors; fall back to an empty prefix when it doesn't parse cleanly.
        if (preg_match('/^(\+\d{1,4})/', $country->receiving_number, $matches)) {
            return $matches[1].' ';
        }

        return '';
    }

    /**
     * @return \Illuminate\Support\Collection<int, MobileMoneyNetwork>
     */
    public function getRecipientNetworksProperty()
    {
        $toCountry = Country::query()->where('currency_code', $this->toCurrency)->first();

        return $toCountry
            ? $toCountry->mobileMoneyNetworks()->active()->orderBy('name')->get()
            : collect();
    }

    public function getFromCountryProperty(): ?Country
    {
        return Country::query()->where('currency_code', $this->fromCurrency)->first();
    }

    public function getRateModelProperty(): ?ExchangeRate
    {
        return ExchangeRate::forCorridor($this->fromCurrency, $this->toCurrency)->first();
    }

    public function getTotalPayProperty(): float
    {
        return $this->rateModel?->totalFor($this->amount) ?? $this->amount;
    }

    public function getRecipientAmountProperty(): float
    {
        return $this->rateModel?->recipientAmountFor($this->amount) ?? 0.0;
    }

    /**
     * True when the amount carried over from the calculator no longer fits this
     * corridor's limits — e.g. an admin lowered the maximum, the link is stale,
     * or someone hand-edited the query string. Checked again here rather than
     * trusted from the calculator page, since that's a separate request.
     */
    public function getAmountOutOfRangeProperty(): bool
    {
        $rate = $this->rateModel;

        if (! $rate) {
            return false;
        }

        if ($rate->min_amount !== null && $this->amount < (float) $rate->min_amount) {
            return true;
        }

        return $rate->max_amount !== null && $this->amount > (float) $rate->max_amount;
    }

    public function submitTransfer(): void
    {
        $rate = $this->rateModel;

        if (! $rate) {
            $this->addError('network', 'This corridor isn\'t available right now — please start again from the calculator.');

            return;
        }

        if ($this->fromCurrency === $this->toCurrency) {
            $this->addError('network', 'The sending and receiving currency can\'t be the same.');

            return;
        }

        $this->validate([
            'amount' => [
                'required',
                'numeric',
                'min:'.($rate->min_amount ?? 1),
                ...($rate->max_amount ? ['max:'.$rate->max_amount] : []),
            ],
            'senderName' => 'required|min:3|max:255',
            'senderPhone' => 'required|min:8|max:30',
            'recipientName' => 'required|min:3|max:255',
            'recipientPhone' => 'required|min:8|max:30',
            'network' => 'required',
            'transactionCode' => 'required|min:4|max:50',
        ], [
            'amount.min' => "The amount must be at least :min {$this->fromCurrency} for this corridor.",
            'amount.max' => "The amount can't exceed :max {$this->fromCurrency} for this corridor.",
            'senderName.required' => 'Please enter your name.',
            'senderName.min' => 'Your name looks too short — please check it.',
            'senderPhone.required' => 'Please enter your mobile phone number.',
            'senderPhone.min' => 'That phone number looks too short — please check it.',
            'recipientName.required' => "Please enter the recipient's name.",
            'recipientName.min' => "The recipient's name looks too short — please check it.",
            'recipientPhone.required' => "Please enter the recipient's mobile phone number.",
            'recipientPhone.min' => "That recipient phone number looks too short — please check it.",
            'network.required' => 'Please choose the recipient\'s mobile money network.',
            'transactionCode.required' => 'Please enter the mobile money transaction code you received after paying.',
            'transactionCode.min' => 'That transaction code looks too short — please double-check it against your mobile money confirmation.',
        ]);

        $rateLimitKey = 'send-money:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $this->addError('transactionCode', 'Too many transfer submissions from this connection. Please wait '.RateLimiter::availableIn($rateLimitKey).' seconds and try again, or contact support if this is urgent.');

            return;
        }

        RateLimiter::hit($rateLimitKey, decaySeconds: 600);

        $transaction = Transaction::createWithReference([
            'from_currency' => $this->fromCurrency,
            'to_currency' => $this->toCurrency,
            'amount_sent' => $this->amount,
            'exchange_rate' => $rate->rate,
            'market_rate' => $rate->market_rate,
            'fee' => $rate->fee,
            'total_paid' => $this->totalPay,
            'recipient_amount' => $this->recipientAmount,
            'customer_name' => $this->senderName,
            'customer_phone' => $this->senderPhone,
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'recipient_network' => $this->network,
            'payment_reference' => $this->transactionCode,
        ]);

        $this->transactionReference = $transaction->reference;
        $this->submitted = true;
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    @if ($submitted)
        <!-- Success State -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400">
                <flux:icon.check-circle class="size-8 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Transfer Submitted Successfully</h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Transaction ID: <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $transactionReference }}</span></p>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3 dark:border-zinc-800 dark:bg-zinc-950/50">
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Sender</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $senderName }} ({{ $senderPhone }})</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Recipient</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $recipientName }} ({{ $recipientPhone }})</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Amount Sent</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ number_format($amount, 2) }} {{ $fromCurrency }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-white border-t border-slate-200 pt-2 dark:border-zinc-800">
                    <span>Payout Amount</span>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($this->recipientAmount, 2) }} {{ $toCurrency }}</span>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="/track?id={{ urlencode($transactionReference) }}" wire:navigate class="w-full">
                    <flux:button variant="primary" icon="magnifying-glass" class="w-full justify-center">
                        Track Transfer Status
                    </flux:button>
                </a>
                <a href="/" wire:navigate class="w-full">
                    <flux:button variant="subtle" icon="home" class="w-full justify-center">
                        Back to Home
                    </flux:button>
                </a>
            </div>
        </div>
    @else
        @if (! $this->rateModel)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
                This corridor isn't available right now. <a href="/" wire:navigate class="underline">Start again from the calculator</a>.
            </div>
        @elseif ($this->amountOutOfRange)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
                {{ number_format($amount, 2) }} {{ $fromCurrency }} is outside the allowed range for this corridor
                ({{ number_format((float) $this->rateModel->min_amount, 0) }}–{{ $this->rateModel->max_amount ? number_format((float) $this->rateModel->max_amount, 0) : '∞' }} {{ $fromCurrency }}).
                <a href="/" wire:navigate class="underline">Start again from the calculator</a>.
            </div>
        @endif

        <!-- Step 1: Deposit Instructions Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <div class="border-b border-slate-100 pb-4 dark:border-zinc-800">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    <span>01</span><span>&bull;</span><span>Deposit Instructions</span>
                </div>
                <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Pay {{ number_format($this->totalPay, 2) }} {{ $fromCurrency }}</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">Send payment to the official SafiFX mobile money number below before submitting.</p>
            </div>

            @if ($this->fromCountry)
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 dark:border-zinc-800 dark:bg-zinc-950/50">
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                        <span>Payment Channel</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $this->fromCountry->receiving_network }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                        <span>Number / Till</span>
                        <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $this->fromCountry->receiving_number }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                        <span>Account Name</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $this->fromCountry->receiving_account_name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400 border-t border-slate-200 pt-2 dark:border-zinc-800">
                        <span>Total Required</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->totalPay, 2) }} {{ $fromCurrency }}</span>
                    </div>
                </div>
            @else
                <p class="mt-4 text-xs font-medium text-rose-600 dark:text-rose-400">No receiving account is configured for {{ $fromCurrency }} yet.</p>
            @endif
        </div>

        <!-- Step 2: Sender & Recipient Information Form -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <div class="border-b border-slate-100 pb-4 dark:border-zinc-800">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    <span>02</span><span>&bull;</span><span>Sender & Recipient Details</span>
                </div>
                <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Provide transfer information</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">Enter your sender details, recipient mobile account, and mobile money transaction code.</p>
            </div>

            <form wire:submit="submitTransfer" class="mt-6 space-y-5">
                <!-- Sender Info Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Your Name <span class="text-rose-500">*</span></flux:label>
                        <flux:input wire:model.live.blur="senderName" icon="user" placeholder="e.g. John Doe" required />
                        <flux:error name="senderName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Your Mobile Phone <span class="text-rose-500">*</span></flux:label>
                        <flux:input wire:model.live.blur="senderPhone" icon="phone" placeholder="e.g. +254 712 345678" required />
                        <flux:error name="senderPhone" />
                    </flux:field>
                </div>

                <!-- Recipient Info Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Recipient Name <span class="text-rose-500">*</span></flux:label>
                        <flux:input wire:model.live.blur="recipientName" icon="user" placeholder="e.g. Jane Smith" required />
                        <flux:error name="recipientName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Recipient Mobile Phone <span class="text-rose-500">*</span></flux:label>
                        <flux:input wire:model.live.blur="recipientPhone" icon="phone" placeholder="e.g. +256 770 123456" required />
                        <flux:error name="recipientPhone" />
                    </flux:field>
                </div>

                <!-- Network & Transaction Code -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Recipient Network <span class="text-rose-500">*</span></flux:label>
                        <flux:select wire:model.live="network" icon="signal">
                            @forelse ($this->recipientNetworks as $net)
                                <option value="{{ $net->name }}">{{ $net->name }}</option>
                            @empty
                                <option value="">No networks configured</option>
                            @endforelse
                        </flux:select>
                        <flux:error name="network" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Transaction Code <span class="text-rose-500">*</span></flux:label>
                        <flux:input wire:model.live.blur="transactionCode" icon="receipt-percent" placeholder="e.g. QKH89210XZ" required />
                        <flux:error name="transactionCode" />
                    </flux:field>
                </div>

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                    class="w-full justify-center text-base font-bold"
                    :disabled="! $this->rateModel || $this->amountOutOfRange"
                >
                    Submit Transfer Payment
                </flux:button>
            </form>
        </div>
    @endif
</div>
