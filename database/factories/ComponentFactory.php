<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\ComponentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'component_category_id' => ComponentCategory::factory(),
            'code' => Str::upper(Str::random(10)),
            'name' => Str::title(fake()->unique()->words(2, true)),
            'description' => fake()->optional()->sentence(),
            'manufacturer' => fake()->company(),
            'manufacturer_part_number' => Str::upper(Str::random(8)),
            'internal_part_number' => Str::upper(Str::random(8)),
            'sales_part_number' => Str::upper(Str::random(8)),
            'cost' => fake()->randomFloat(2, 1, 500),
            'sales_price' => fake()->randomFloat(2, 1, 1000),
            'currency' => 'EUR',
            'weight' => fake()->randomFloat(3, 0, 50),
            'dimensions' => null,
            'technical_specs' => null,
            'status' => 'active',
        ];
    }
}
