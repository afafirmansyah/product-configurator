<?php

namespace Database\Factories;

use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
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
            'price_list_id' => PriceList::factory(),
            'base_price' => fake()->randomFloat(2, 10, 1000),
            'valid_from' => null,
            'valid_to' => null,
        ];
    }
}
