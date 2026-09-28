<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Seed starter corridor rates. These are illustrative defaults — the whole
     * point of docs/SAFIFX.md §5/§6 is that rates, fees and limits are controlled
     * from the admin Exchange Rates screen from here on, not hardcoded in the app.
     *
     * Uses `firstOrCreate` keyed on (from_currency, to_currency): a corridor is
     * seeded once, and re-running this seeder (e.g. because it runs on every
     * deploy, per docs/SAFIFX.md's request) never overwrites a rate an admin has
     * since edited through the Filament resource.
     */
    public function run(): void
    {
        $rates = [
            ['from_currency' => 'KES', 'to_currency' => 'UGX', 'rate' => 28.00, 'market_rate' => 28.20, 'fee' => 200, 'min_amount' => 1000, 'max_amount' => 500000],
            ['from_currency' => 'UGX', 'to_currency' => 'KES', 'rate' => 0.0357, 'market_rate' => 0.0355, 'fee' => 5700, 'min_amount' => 30000, 'max_amount' => 15000000],
            ['from_currency' => 'KES', 'to_currency' => 'TZS', 'rate' => 19.10, 'market_rate' => 19.30, 'fee' => 200, 'min_amount' => 1000, 'max_amount' => 500000],
            ['from_currency' => 'TZS', 'to_currency' => 'KES', 'rate' => 0.0523, 'market_rate' => 0.0518, 'fee' => 3800, 'min_amount' => 20000, 'max_amount' => 10000000],
            ['from_currency' => 'KES', 'to_currency' => 'RWF', 'rate' => 9.10, 'market_rate' => 9.25, 'fee' => 200, 'min_amount' => 1000, 'max_amount' => 500000],
            ['from_currency' => 'RWF', 'to_currency' => 'KES', 'rate' => 0.1099, 'market_rate' => 0.1081, 'fee' => 2000, 'min_amount' => 10000, 'max_amount' => 5000000],
            ['from_currency' => 'KES', 'to_currency' => 'ETB', 'rate' => 1.05, 'market_rate' => 1.08, 'fee' => 200, 'min_amount' => 1000, 'max_amount' => 500000],
            ['from_currency' => 'ETB', 'to_currency' => 'KES', 'rate' => 0.9524, 'market_rate' => 0.9259, 'fee' => 200, 'min_amount' => 1000, 'max_amount' => 500000],
        ];

        foreach ($rates as $rate) {
            ExchangeRate::query()->firstOrCreate(
                [
                    'from_currency' => $rate['from_currency'],
                    'to_currency' => $rate['to_currency'],
                ],
                $rate + ['is_active' => true],
            );
        }
    }
}
