<?php

namespace Database\Factories;

use App\Models\ConfigurationRule;
use App\Models\RuleAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleAction>
 */
class RuleActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'configuration_rule_id' => ConfigurationRule::factory(),
            'action_type' => 'show',
            'target_type' => 'attribute',
            'target_id' => fake()->numberBetween(1, 100),
            'value' => null,
            'sort_order' => 0,
        ];
    }
}
