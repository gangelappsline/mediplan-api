<?php

namespace Tests\Feature\Business;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class AppointmentAgendaTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_business_can_list_its_appointments_filtered_by_date(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $inside = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => now()->copy()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->copy()->addDays(3)->setTime(10, 30),
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => now()->copy()->addDays(20)->setTime(10, 0),
            'ends_at' => now()->copy()->addDays(20)->setTime(10, 30),
        ]);

        Passport::actingAs($owner);

        $from = now()->toDateString();
        $to = now()->copy()->addDays(7)->toDateString();

        $this->getJson("/api/business/appointments?from={$from}&to={$to}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $inside->getKey())
            ->assertJsonStructure(['data' => [['id', 'title', 'starts_at', 'ends_at', 'status' => ['name', 'label', 'is_booked'], 'client' => ['id', 'name']]]]);
    }

    public function test_agenda_groups_appointments_by_day(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $firstDay = now()->copy()->addDays(2)->setTime(9, 0);

        foreach ([9, 11, 15] as $hour) {
            Appointment::factory()->create([
                'business_id' => $business->getKey(),
                'client_id' => $client->getKey(),
                'starts_at' => $firstDay->copy()->setTime($hour, 0),
                'ends_at' => $firstDay->copy()->setTime($hour, 30),
            ]);
        }

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => $firstDay->copy()->addDay()->setTime(12, 0),
            'ends_at' => $firstDay->copy()->addDay()->setTime(12, 30),
        ]);

        Passport::actingAs($owner);

        $response = $this->getJson('/api/business/appointments/agenda?from='.$firstDay->toDateString().'&to='.$firstDay->copy()->addDays(2)->toDateString())
            ->assertOk()
            ->assertJsonPath('message', 'Agenda obtenida correctamente.')
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.from', $firstDay->toDateString())
            ->assertJsonStructure([
                'data' => [
                    'from',
                    'to',
                    'total',
                    'days' => [['date', 'label', 'total', 'appointments' => [['id', 'starts_at']]]],
                ],
            ]);

        $days = $response->json('data.days');

        $this->assertCount(2, $days);
        $this->assertSame($firstDay->toDateString(), $days[0]['date']);
        $this->assertSame(3, $days[0]['total']);
        $this->assertSame(1, $days[1]['total']);
    }

    public function test_agenda_defaults_to_the_current_month(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->getJson('/api/business/appointments/agenda')
            ->assertOk()
            ->assertJsonPath('data.from', now()->startOfMonth()->toDateString())
            ->assertJsonPath('data.to', now()->endOfMonth()->toDateString())
            ->assertJsonPath('data.total', 0);
    }

    public function test_business_can_schedule_an_appointment(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($owner);

        $startsAt = now()->copy()->addDays(2)->setTime(10, 0);

        $this->postJson('/api/business/appointments', [
            'client_id' => $client->getKey(),
            'title' => 'Consulta inicial',
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes(30)->toIso8601String(),
            'price' => 850,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Cita agendada correctamente.')
            ->assertJsonPath('data.business_id', $business->getKey())
            ->assertJsonPath('data.client.id', $client->getKey())
            ->assertJsonPath('data.status.name', AppointmentStatus::Scheduled->value)
            ->assertJsonPath('data.price', 850);

        $this->assertDatabaseHas('appointments', [
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'created_by_user_id' => $owner->getKey(),
        ]);
    }

    public function test_an_appointment_cannot_use_a_client_from_another_business(): void
    {
        [$owner] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();
        $foreignClient = Client::factory()->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $startsAt = now()->copy()->addDay()->setTime(10, 0);

        $this->postJson('/api/business/appointments', [
            'client_id' => $foreignClient->getKey(),
            'title' => 'Cita inválida',
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes(30)->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.client_id.0', 'El cliente seleccionado no pertenece a tu negocio.');
    }

    public function test_overlapping_appointments_are_rejected(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $startsAt = now()->copy()->addDays(4)->setTime(10, 0);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Passport::actingAs($owner);

        $this->postJson('/api/business/appointments', [
            'client_id' => $client->getKey(),
            'title' => 'Cita encimada',
            'starts_at' => $startsAt->copy()->addMinutes(15)->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes(45)->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'Ya existe una cita agendada en ese horario.');
    }

    public function test_an_appointment_cannot_start_in_the_past(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($owner);

        $startsAt = now()->copy()->subDay();

        $this->postJson('/api/business/appointments', [
            'client_id' => $client->getKey(),
            'title' => 'Cita pasada',
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes(30)->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'La cita debe agendarse en una fecha futura.');
    }

    public function test_cancelling_an_appointment_requires_a_reason(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $startsAt = now()->copy()->addDay()->setTime(10, 0);

        $appointment = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
        ]);

        Passport::actingAs($owner);

        $this->patchJson("/api/business/appointments/{$appointment->getKey()}/status", ['status' => 'cancelled'])
            ->assertStatus(422)
            ->assertJsonPath('errors.cancel_reason.0', 'Indica el motivo de la cancelación.');

        $this->patchJson("/api/business/appointments/{$appointment->getKey()}/status", [
            'status' => 'cancelled',
            'cancel_reason' => 'El cliente reprogramó.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.name', AppointmentStatus::Cancelled->value)
            ->assertJsonPath('data.status.is_booked', false)
            ->assertJsonPath('data.cancel_reason', 'El cliente reprogramó.');

        $this->assertNotNull($appointment->refresh()->cancelled_at);
    }

    public function test_completing_an_appointment_frees_the_slot(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $startsAt = now()->copy()->addDays(5)->setTime(16, 0);

        $appointment = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
        ]);

        Passport::actingAs($owner);

        $this->patchJson("/api/business/appointments/{$appointment->getKey()}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status.name', AppointmentStatus::Completed->value);

        // El horario queda libre para otra cita.
        $this->postJson('/api/business/appointments', [
            'client_id' => $client->getKey(),
            'title' => 'Nueva cita en el mismo horario',
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes(30)->toIso8601String(),
        ])->assertCreated();
    }

    public function test_appointments_are_isolated_between_businesses(): void
    {
        [$owner] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();
        $foreignClient = Client::factory()->create(['business_id' => $otherBusiness->getKey()]);

        $foreignAppointment = Appointment::factory()->create([
            'business_id' => $otherBusiness->getKey(),
            'client_id' => $foreignClient->getKey(),
        ]);

        Passport::actingAs($owner);

        $this->getJson("/api/business/appointments/{$foreignAppointment->getKey()}")->assertNotFound();
        $this->deleteJson("/api/business/appointments/{$foreignAppointment->getKey()}")->assertNotFound();
        $this->assertDatabaseHas('appointments', ['id' => $foreignAppointment->getKey()]);
    }
}
