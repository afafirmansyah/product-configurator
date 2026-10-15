<?php

namespace Database\Factories;

use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PriceList>
 */
class PriceListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->words(2, true)),
            'code' => Str::upper(Str::random(10)),
            'currency' => 'EUR',
            'is_default' => false,
            'is_active' => true,
            'valid_from' => null,
            'valid_to' => null,
        ];
    }

    /**
     * Indicate that the price list is the default list.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
