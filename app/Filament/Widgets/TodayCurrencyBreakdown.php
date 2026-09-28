<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * A per-currency table of today's money figures, split out from
 * TodayOverview's stat cards — see that class's docblock for why.
 */
class TodayCurrencyBreakdown extends Widget
{
    protected string $view = 'filament.widgets.today-currency-breakdown';

    protected static ?int $sort = 2;

    /**
     * @return array<int, array{currency: string, received: float, paid_out: float, fees: float, fx_revenue: float}>
     */
    public function getRows(): array
    {
        $today = Transaction::query()->today();

        // Received & fees are charged in from_currency (what the customer paid in).
        $received = (clone $today)
            ->selectRaw('from_currency, SUM(total_paid) as total')
            ->groupBy('from_currency')
            ->pluck('total', 'from_currency');

        $fees = (clone $today)
            ->selectRaw('from_currency, SUM(fee) as total')
            ->groupBy('from_currency')
            ->pluck('total', 'from_currency');

        // Paid out is what actually reached recipients — to_currency, completed only.
        $paidOut = (clone $today)
            ->where('status', TransactionStatus::Completed)
            ->selectRaw('to_currency, SUM(recipient_amount) as total')
            ->groupBy('to_currency')
            ->pluck('total', 'to_currency');

        // FX revenue (customer-rate vs market-rate spread × volume) is realized in
        // from_currency, same as the fee.
        $fxRevenue = (clone $today)
            ->whereNotNull('market_rate')
            ->get()
            ->groupBy('from_currency')
            ->map(fn (Collection $transactions) => $transactions->sum(
                fn (Transaction $transaction) => (float) $transaction->amount_sent * ((float) $transaction->market_rate - (float) $transaction->exchange_rate)
            ));

        $currencies = collect([$received, $fees, $paidOut, $fxRevenue])
            ->flatMap(fn (Collection $totals) => $totals->keys())
            ->unique()
            ->sort()
            ->values();

        return $currencies
            ->map(fn (string $currency) => [
                'currency' => $currency,
                'received' => (float) ($received[$currency] ?? 0),
                'paid_out' => (float) ($paidOut[$currency] ?? 0),
                'fees' => (float) ($fees[$currency] ?? 0),
                'fx_revenue' => (float) ($fxRevenue[$currency] ?? 0),
            ])
            ->all();
    }
}
