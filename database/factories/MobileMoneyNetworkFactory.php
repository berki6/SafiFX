<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\MobileMoneyNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileMoneyNetwork>
 */
class MobileMoneyNetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->unique()->company().' Money',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
