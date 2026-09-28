<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountSent = fake()->randomFloat(2, 1000, 50000);
        $rate = 28.00;
        $fee = 200;

        return [
            'reference' => Transaction::generateReference(),
            'status' => TransactionStatus::PaymentSubmitted,
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'amount_sent' => $amountSent,
            'exchange_rate' => $rate,
            'market_rate' => 28.20,
            'fee' => $fee,
            'total_paid' => $amountSent + $fee,
            'recipient_amount' => round($amountSent * $rate, 2),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->e164PhoneNumber(),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->e164PhoneNumber(),
            'recipient_network' => 'MTN Mobile Money',
            'payment_reference' => strtoupper(fake()->bothify('??########')),
            'payout_reference' => null,
            'processed_by_id' => null,
            'payment_verified_at' => null,
            'completed_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::PaymentVerified,
            'payment_verified_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Completed,
            'payment_verified_at' => now()->subHour(),
            'payout_reference' => strtoupper(fake()->bothify('PO########')),
            'completed_at' => now(),
        ]);
    }
}
