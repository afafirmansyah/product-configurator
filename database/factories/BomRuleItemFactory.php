<?php

namespace Database\Factories;

use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomRuleItem>
 */
class BomRuleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bom_rule_id' => BomRule::factory(),
            'component_id' => Component::factory(),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'is_mandatory' => true,
        ];
    }
}
