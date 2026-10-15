<?php

namespace Database\Factories;

use App\Models\ComponentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ComponentCategory>
 */
class ComponentCategoryFactory extends Factory
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
            'is_active' => true,
        ];
    }
}
