<?php

namespace Database\Factories;

use App\Models\PartNumberPattern;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PartNumberPattern>
 */
class PartNumberPatternFactory extends Factory
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
            'name' => Str::title(fake()->unique()->words(2, true)),
            'segments' => [
                ['type' => 'literal', 'value' => 'HEC', 'separator' => '-'],
            ],
            'is_active' => true,
        ];
    }
}
