<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+3 weeks');

        return [
            'business_id' => Business::factory(),
            'client_id' => Client::factory(),
            'user_id' => null,
            'title' => fake()->randomElement([
                'Consulta inicial',
                'Seguimiento',
                'Sesión de valoración',
                'Aplicación de tratamiento',
                'Revisión general',
            ]),
            'description' => fake()->optional()->sentence(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+30 minutes'),
            'status' => AppointmentStatus::Scheduled,
            'price' => fake()->randomFloat(2, 200, 3000),
        ];
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /**
     * Cita en el pasado, útil para históricos del dashboard.
     */
    public function past(): static
    {
        return $this->state(function () {
            $startsAt = fake()->dateTimeBetween('-3 weeks', '-1 hour');

            return [
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->modify('+30 minutes'),
                'status' => AppointmentStatus::Completed,
            ];
        });
    }

    public function today(): static
    {
        return $this->state(function () {
            $startsAt = now()->setTime(
                (int) fake()->numberBetween(9, 17),
                (int) fake()->randomElement([0, 30]),
            );

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes(30),
                'status' => AppointmentStatus::Confirmed,
            ];
        });
    }
}
