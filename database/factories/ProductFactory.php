<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'ulid' => (string) Str::ulid(),
            'product_family_id' => ProductFamily::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'code' => Str::upper(Str::random(8)),
            'sku' => Str::upper(Str::random(10)),
            'manufacturer' => fake()->company(),
            'short_description' => fake()->sentence(),
            'base_price' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'EUR',
            'status' => ProductStatus::Draft->value,
            'version' => '1.0',
            'is_configurable' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the product is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Published->value,
        ]);
    }
}
