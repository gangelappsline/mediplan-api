<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('55########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'notes' => fake()->optional()->sentence(),
            'status' => ClientStatus::Active,
            'last_appointment_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ClientStatus::Inactive]);
    }
}
