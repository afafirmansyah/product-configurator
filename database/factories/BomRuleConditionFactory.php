<?php

namespace Database\Factories;

use App\Enums\RuleOperator;
use App\Models\Attribute;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomRuleCondition>
 */
class BomRuleConditionFactory extends Factory
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
            'attribute_id' => Attribute::factory(),
            'operator' => RuleOperator::Equals->value,
            'value' => (string) fake()->numberBetween(1, 100),
            'logic' => 'AND',
            'group' => 0,
        ];
    }
}
