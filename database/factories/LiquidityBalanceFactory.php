<?php

namespace Database\Factories;

use App\Models\LiquidityBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiquidityBalance>
 */
class LiquidityBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency' => fake()->unique()->currencyCode(),
            'available_amount' => fake()->randomFloat(2, 100000, 5000000),
            'low_threshold' => 500000,
        ];
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_amount' => 100,
            'low_threshold' => 500000,
        ]);
    }
}
