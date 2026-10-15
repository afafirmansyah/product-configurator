<?php

namespace Database\Factories;

use App\Models\AttributeOption;
use App\Models\OptionPrice;
use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OptionPrice>
 */
class OptionPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attribute_option_id' => AttributeOption::factory(),
            'price_list_id' => PriceList::factory(),
            'price_adjustment' => fake()->randomFloat(2, 0, 500),
            'valid_from' => null,
            'valid_to' => null,
        ];
    }
}
