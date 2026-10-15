<?php

namespace Database\Factories;

use App\Enums\RuleOperator;
use App\Models\Attribute;
use App\Models\ConfigurationRule;
use App\Models\RuleCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleCondition>
 */
class RuleConditionFactory extends Factory
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
            'attribute_id' => Attribute::factory(),
            'operator' => RuleOperator::Equals->value,
            'value' => (string) fake()->numberBetween(1, 100),
            'logic' => 'AND',
            'group' => 0,
            'sort_order' => 0,
        ];
    }
}
