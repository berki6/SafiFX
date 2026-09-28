<?php

use Livewire\Component;

new class extends Component {
    public string $fromCurrency = 'KES';
    public string $toCurrency = 'UGX';
    public float $amount = 10000;

    public string $senderName = '';
    public string $senderPhone = '';
    public string $recipientName = '';
    public string $recipientPhone = '';
    public string $network = 'MTN Mobile Money';
    public string $transactionCode = '';

    public bool $submitted = false;
    public string $transactionId = '';

    public array $depositDetails = [
        'KES' => ['pay' => 'M-PESA Paybill', 'num' => '522522', 'acct' => 'SafiFX Kenya Ltd', 'networks' => ['M-PESA', 'Airtel Money']],
        'UGX' => ['pay' => 'MTN / Airtel Merchant', 'num' => '+256 770 123456', 'acct' => 'SafiFX Uganda', 'networks' => ['MTN Mobile Money', 'Airtel Money']],
        'TZS' => ['pay' => 'Vodacom M-Pesa', 'num' => '+255 750 987654', 'acct' => 'SafiFX Tanzania', 'networks' => ['Vodacom M-Pesa', 'Tigo Pesa', 'Airtel Money']],
        'RWF' => ['pay' => 'MTN MoMo Rwanda', 'num' => '+250 788 112233', 'acct' => 'SafiFX Rwanda', 'networks' => ['MTN MoMo', 'Airtel Money']],
        'USD' => ['pay' => 'Bank Wire / Mobile Payment', 'num' => 'US-89102-SAFI', 'acct' => 'SafiFX Global', 'networks' => ['Bank Transfer', 'M-PESA Global']],
    ];

    public function mount(): void
    {
        $this->fromCurrency = request()->query('from', 'KES');
        $this->toCurrency = request()->query('to', 'UGX');
        $this->amount = (float) request()->query('amount', 10000);

        $fromPrefix = $this->getPhonePrefix($this->fromCurrency);
        $toPrefix = $this->getPhonePrefix($this->toCurrency);

        $this->senderPhone = $fromPrefix . ' ';
        $this->recipientPhone = $toPrefix . ' ';

        $networks = $this->depositDetails[$this->toCurrency]['networks'] ?? ['Mobile Money'];
        $this->network = $networks[0];
    }

    private function getPhonePrefix(string $currency): string
    {
        return match($currency) {
            'KES' => '+254',
            'UGX' => '+256',
            'TZS' => '+255',
            'RWF' => '+250',
            'USD' => '+1',
            default => '+254',
        };
    }

    public function getRateProperty(): float
    {
        $rates = [
            'KES-UGX' => 28.5,
            'UGX-KES' => 0.035,
            'KES-TZS' => 19.2,
            'TZS-KES' => 0.052,
            'USD-KES' => 129.50,
            'KES-USD' => 0.0077,
        ];
        return $rates["{$this->fromCurrency}-{$this->toCurrency}"] ?? 1.0;
    }

    public function getFeeProperty(): float
    {
        return round($this->amount * 0.005, 2);
    }

    public function getRecipientAmountProperty(): float
    {
        return round(max(0, $this->amount - $this->fee) * $this->rate, 2);
    }

    public function submitTransfer(): void
    {
        $this->validate([
            'senderName' => 'required|min:3|max:255',
            'senderPhone' => 'required|min:8|max:30',
            'recipientName' => 'required|min:3|max:255',
            'recipientPhone' => 'required|min:8|max:30',
            'network' => 'required',
            'transactionCode' => 'required|min:4|max:50',
        ]);

        $this->transactionId = 'SFX-' . date('Ymd') . '-' . rand(10000, 99999);
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
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Transaction ID: <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $transactionId }}</span></p>
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
                <a href="/track?id={{ urlencode($transactionId) }}" wire:navigate class="w-full">
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
        <!-- Step 1: Deposit Instructions Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <div class="border-b border-slate-100 pb-4 dark:border-zinc-800">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    <span>01</span><span>&bull;</span><span>Deposit Instructions</span>
                </div>
                <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Pay {{ number_format($amount, 2) }} {{ $fromCurrency }}</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">Send payment to the official SafiFX mobile money number below before submitting.</p>
            </div>

            @php $deposit = $depositDetails[$fromCurrency] ?? $depositDetails['KES']; @endphp

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 dark:border-zinc-800 dark:bg-zinc-950/50">
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Payment Channel</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $deposit['pay'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Number / Till</span>
                    <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $deposit['num'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400">
                    <span>Account Name</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $deposit['acct'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-zinc-400 border-t border-slate-200 pt-2 dark:border-zinc-800">
                    <span>Total Required</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($amount, 2) }} {{ $fromCurrency }}</span>
                </div>
            </div>
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
                        <flux:label>Your Name</flux:label>
                        <flux:input wire:model.live.blur="senderName" icon="user" placeholder="e.g. John Doe" required />
                        <flux:error name="senderName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Your Mobile Phone</flux:label>
                        <flux:input wire:model.live.blur="senderPhone" icon="phone" placeholder="e.g. +254 712 345678" required />
                        <flux:error name="senderPhone" />
                    </flux:field>
                </div>

                <!-- Recipient Info Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Recipient Name</flux:label>
                        <flux:input wire:model.live.blur="recipientName" icon="user" placeholder="e.g. Jane Smith" required />
                        <flux:error name="recipientName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Recipient Mobile Phone</flux:label>
                        <flux:input wire:model.live.blur="recipientPhone" icon="phone" placeholder="e.g. +256 770 123456" required />
                        <flux:error name="recipientPhone" />
                    </flux:field>
                </div>

                <!-- Network & Transaction Code -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Recipient Network</flux:label>
                        <flux:select wire:model.live="network" icon="signal">
                            @foreach ($depositDetails[$toCurrency]['networks'] ?? ['Mobile Money'] as $net)
                                <option value="{{ $net }}">{{ $net }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="network" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Transaction Code</flux:label>
                        <flux:input wire:model.live.blur="transactionCode" icon="receipt-percent" placeholder="e.g. QKH89210XZ" required />
                        <flux:error name="transactionCode" />
                    </flux:field>
                </div>

                <flux:button 
                    type="submit" 
                    variant="primary" 
                    icon="check"
                    class="w-full justify-center text-base font-bold"
                >
                    Submit Transfer Payment
                </flux:button>
            </form>
        </div>
    @endif
</div>
