<?php

namespace Database\Factories;

use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConfigurationSession>
 */
class ConfigurationSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'configuration_number' => 'CFG-'.date('Y').'-'.fake()->unique()->numerify('######'),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'status' => 'draft',
            'bom_status' => 'pending',
            'bom_revision' => 0,
            'version' => '1',
            'total_price' => 0,
            'currency' => 'EUR',
        ];
    }

    /**
     * Assign the configuration session to the given user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
