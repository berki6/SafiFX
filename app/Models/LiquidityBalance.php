<?php

namespace App\Models;

use Database\Factories\LiquidityBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $currency
 * @property float $available_amount
 * @property float|null $low_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['currency', 'available_amount', 'low_threshold'])]
class LiquidityBalance extends Model
{
    /** @use HasFactory<LiquidityBalanceFactory> */
    use HasFactory;

    /**
     * Whether the available balance is at or below its configured low threshold.
     */
    public function isLow(): bool
    {
        if ($this->low_threshold === null) {
            return false;
        }

        return (float) $this->available_amount <= (float) $this->low_threshold;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_amount' => 'decimal:2',
            'low_threshold' => 'decimal:2',
        ];
    }
}
