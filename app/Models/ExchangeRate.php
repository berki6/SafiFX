<?php

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $from_currency
 * @property string $to_currency
 * @property float $rate
 * @property float|null $market_rate
 * @property float $fee
 * @property float|null $min_amount
 * @property float|null $max_amount
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'from_currency',
    'to_currency',
    'rate',
    'market_rate',
    'fee',
    'min_amount',
    'max_amount',
    'is_active',
])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    /**
     * @param  Builder<ExchangeRate>  $query
     * @return Builder<ExchangeRate>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The active rate configured for a currency corridor, e.g. KES -> UGX.
     *
     * @param  Builder<ExchangeRate>  $query
     * @return Builder<ExchangeRate>
     */
    #[Scope]
    protected function forCorridor(Builder $query, string $fromCurrency, string $toCurrency): Builder
    {
        return $query
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->active();
    }

    /**
     * The flat fee plus amount sent, i.e. the total the customer must pay.
     *
     * The fee is charged on top of the sending amount, not deducted from it
     * (see docs/SAFIFX.md §4: "You send KES 10,000 ... Total you pay KES 10,200").
     */
    public function totalFor(float $amountSent): float
    {
        return round($amountSent + (float) $this->fee, 2);
    }

    /**
     * The amount the recipient receives: the sending amount converted at the
     * customer rate. The fee does not reduce this — it is paid on top by the
     * sender (see docs/SAFIFX.md §4).
     */
    public function recipientAmountFor(float $amountSent): float
    {
        return round($amountSent * (float) $this->rate, 2);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'market_rate' => 'decimal:6',
            'fee' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
