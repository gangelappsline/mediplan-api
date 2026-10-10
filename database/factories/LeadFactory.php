<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('55########'),
            'company' => fake()->optional()->company(),
            'source' => fake()->randomElement(['facebook', 'instagram', 'referencia', 'sitio_web', 'whatsapp', 'otro']),
            'status' => LeadStatus::New,
            'estimated_value' => fake()->randomFloat(2, 500, 25000),
            'notes' => fake()->optional()->sentence(),
            'follow_up_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'contacted_at' => null,
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'contacted_at' => $status === LeadStatus::New ? null : now(),
        ]);
    }
}
