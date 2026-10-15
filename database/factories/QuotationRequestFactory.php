<?php

namespace Database\Factories;

use App\Models\ConfigurationSession;
use App\Models\QuotationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuotationRequest>
 */
class QuotationRequestFactory extends Factory
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
            'reference_number' => 'QR-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'configuration_session_id' => ConfigurationSession::factory(),
            'user_id' => User::factory(),
            'company' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'project_name' => fake()->words(2, true),
            'project_location' => fake()->city(),
            'quantity' => fake()->numberBetween(1, 10),
            'required_delivery_date' => null,
            'remarks' => null,
            'status' => 'pending',
        ];
    }

    /**
     * Assign the quotation to the given submitting user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
