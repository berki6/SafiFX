<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

/**
 * A per-currency table of today's money figures, split out from
 * TodayOverview's stat cards — see that class's docblock for why.
 *
 * Built as a genuine Filament table (via ->records(), for the non-Eloquent
 * per-currency array) rather than hand-rolled HTML: a first attempt used raw
 * Tailwind utility classes in a custom Blade view, but the admin panel's CSS
 * is a static bundle shipped by the filament/filament package itself — it
 * only contains classes Filament's own components already use, and never
 * gets regenerated from this app's custom views. Utility classes like pe-4
 * or border-gray-200 simply weren't in it, so the table rendered with no
 * spacing at all. Filament's own Table component doesn't have that problem,
 * since its classes are obviously already present.
 */
class TodayCurrencyBreakdown extends TableWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading("Today's Money, by Currency")
            ->description("Each corridor settles in its own currency — these aren't blended into one number.")
            ->records(fn (): array => $this->getRows())
            ->columns([
                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('received')
                    ->label('Received')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('paid_out')
                    ->label('Paid Out')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('fees')
                    ->label('Fees')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('fx_revenue')
                    ->label('FX Revenue')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (array $record): ?string => $record['fx_revenue'] < 0 ? 'danger' : null),
            ])
            ->paginated(false)
            ->emptyStateHeading('No transactions yet today');
    }

    /**
     * @return array<string, array{currency: string, received: float, paid_out: float, fees: float, fx_revenue: float}>
     */
    private function getRows(): array
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
            ->mapWithKeys(fn (string $currency) => [
                $currency => [
                    'currency' => $currency,
                    'received' => (float) ($received[$currency] ?? 0),
                    'paid_out' => (float) ($paidOut[$currency] ?? 0),
                    'fees' => (float) ($fees[$currency] ?? 0),
                    'fx_revenue' => (float) ($fxRevenue[$currency] ?? 0),
                ],
            ])
            ->all();
    }
}
