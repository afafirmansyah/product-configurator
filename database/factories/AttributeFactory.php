<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
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
            'type' => AttributeType::Select->value,
            'unit' => null,
            'description' => fake()->optional()->sentence(),
            'validation_rules' => null,
            'is_global' => false,
            'is_active' => true,
        ];
    }

    /**
     * Attach the given number of options to the attribute.
     */
    public function withOptions(int $count = 2): static
    {
        return $this->has(AttributeOption::factory()->count($count), 'options');
    }

    /**
     * Set the attribute type.
     */
    public function ofType(AttributeType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type->value,
        ]);
    }
}
