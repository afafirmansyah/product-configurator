<?php

namespace Database\Factories;

use App\Models\BomRule;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BomRule>
 */
class BomRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => Str::title(fake()->unique()->words(3, true)),
            'code' => 'BR-'.Str::upper(Str::random(10)),
            'condition_logic' => 'AND',
            'is_active' => true,
            'effective_from' => null,
            'effective_to' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * Set the condition logic to OR.
     */
    public function orLogic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'condition_logic' => 'OR',
        ]);
    }
}
