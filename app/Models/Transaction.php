<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Observers\TransactionObserver;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property TransactionStatus $status
 * @property string $from_currency
 * @property string $to_currency
 * @property float $amount_sent
 * @property float $exchange_rate
 * @property float|null $market_rate
 * @property float $fee
 * @property float $total_paid
 * @property float $recipient_amount
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property string $recipient_network
 * @property string $payment_reference
 * @property string|null $payout_reference
 * @property int|null $processed_by_id
 * @property Carbon|null $payment_verified_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference',
    'status',
    'from_currency',
    'to_currency',
    'amount_sent',
    'exchange_rate',
    'market_rate',
    'fee',
    'total_paid',
    'recipient_amount',
    'customer_name',
    'customer_phone',
    'customer_email',
    'recipient_name',
    'recipient_phone',
    'recipient_network',
    'payment_reference',
    'payout_reference',
    'processed_by_id',
    'payment_verified_at',
    'completed_at',
])]
#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    /**
     * The SafiFX receiving channel the customer paid into (docs/SAFIFX.md §14
     * "Payment Method"), looked up from the source country's configuration.
     */
    public function paymentMethod(): ?string
    {
        return Country::query()->where('currency_code', $this->from_currency)->value('receiving_network');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    #[Scope]
    protected function today(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Generate a unique SafiFX transaction reference, e.g. SFX-20260928-00124.
     */
    public static function generateReference(): string
    {
        do {
            $reference = sprintf(
                'SFX-%s-%05d',
                now()->format('Ymd'),
                random_int(1, 99999),
            );
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Create a transaction, retrying with a freshly generated reference if a
     * race with another request collides on the unique `reference` column
     * (the exists() check in generateReference() narrows but can't close that
     * window). Any other database failure is rethrown as-is.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createWithReference(array $attributes): self
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return self::create([
                    'reference' => self::generateReference(),
                    ...$attributes,
                ]);
            } catch (QueryException $exception) {
                $isUniqueReferenceViolation = $exception->getCode() === '23000'
                    && str_contains($exception->getMessage(), 'reference');

                if (! $isUniqueReferenceViolation || $attempts >= 3) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'amount_sent' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'market_rate' => 'decimal:6',
            'fee' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'recipient_amount' => 'decimal:2',
            'payment_verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
