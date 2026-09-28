<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency' => 'KES',
            'to_currency' => 'UGX',
            'rate' => 28.00,
            'market_rate' => 28.20,
            'fee' => 200,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forCorridor(string $from, string $to): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => $from,
            'to_currency' => $to,
        ]);
    }
}
