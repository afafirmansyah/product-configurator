<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\CompatibilityRule;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompatibilityRule>
 */
class CompatibilityRuleFactory extends Factory
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
            'attribute_id' => Attribute::factory(),
            'option_id' => AttributeOption::factory(),
            'target_attribute_id' => Attribute::factory(),
            'target_option_id' => AttributeOption::factory(),
            'type' => 'incompatible',
            'reason' => fake()->optional()->sentence(),
            'engineering_note' => null,
            'priority' => 0,
            'is_active' => true,
            'effective_from' => null,
            'effective_to' => null,
        ];
    }
}
