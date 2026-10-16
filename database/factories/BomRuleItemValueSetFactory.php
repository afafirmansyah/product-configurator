<?php

namespace Database\Factories;

use App\Models\BomRuleItem;
use App\Models\BomRuleItemValueSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomRuleItemValueSet>
 */
class BomRuleItemValueSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bom_rule_item_id' => BomRuleItem::factory(),
            'name' => fake()->optional()->words(2, true),
        ];
    }

    /**
     * Attach the given attribute options to the created set.
     *
     * @param  list<int>  $optionIds
     */
    public function withOptions(array $optionIds): static
    {
        return $this->afterCreating(function (BomRuleItemValueSet $valueSet) use ($optionIds): void {
            $valueSet->options()->attach($optionIds);
        });
    }
}
