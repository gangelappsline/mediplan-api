<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(fn () => []),
            'name' => fake()->company(),
            'description' => fake()->sentence(12),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('55########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'status' => BusinessStatus::Active,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => BusinessStatus::Pending]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => BusinessStatus::Suspended]);
    }
}
