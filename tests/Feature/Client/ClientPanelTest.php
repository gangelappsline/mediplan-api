<?php

namespace Tests\Feature\Client;

use App\Enums\AppointmentStatus;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class ClientPanelTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_client_dashboard_summarizes_the_account(): void
    {
        $user = $this->makeUser([], RoleName::Client);
        [, $business] = $this->makeBusinessOwner();
        $clientProfile = Client::factory()->create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $upcoming = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->copy()->addDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->subDays(4)->setTime(10, 0),
            'ends_at' => now()->copy()->subDays(4)->setTime(10, 30),
            'status' => AppointmentStatus::Completed,
        ]);

        Passport::actingAs($user);

        $this->getJson('/api/client/dashboard')
            ->assertOk()
            ->assertJsonPath('message', 'Panel del cliente generado correctamente.')
            ->assertJsonPath('data.user.id', $user->getKey())
            ->assertJsonPath('data.appointments.total', 2)
            ->assertJsonPath('data.appointments.upcoming_count', 1)
            ->assertJsonPath('data.appointments.completed', 1)
            ->assertJsonPath('data.appointments.next.id', $upcoming->getKey())
            ->assertJsonPath('data.businesses.registered_in', 1)
            ->assertJsonPath('data.businesses.available', 1)
            ->assertJsonCount(1, 'data.upcoming_appointments');
    }

    public function test_client_can_list_its_appointments_by_scope(): void
    {
        $user = $this->makeUser([], RoleName::Client);
        [, $business] = $this->makeBusinessOwner();
        $clientProfile = Client::factory()->create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->addDay()->setTime(10, 0),
            'ends_at' => now()->copy()->addDay()->setTime(10, 30),
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->subDays(2)->setTime(10, 0),
            'ends_at' => now()->copy()->subDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Completed,
        ]);

        Passport::actingAs($user);

        $this->getJson('/api/client/appointments')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/client/appointments?scope=upcoming')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/client/appointments?scope=past')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status.name', AppointmentStatus::Completed->value);
    }

    public function test_client_cannot_see_someone_elses_appointment(): void
    {
        $user = $this->makeUser([], RoleName::Client);
        $someoneElse = $this->makeUser([], RoleName::Client);
        [, $business] = $this->makeBusinessOwner();

        $foreignProfile = Client::factory()->create([
            'business_id' => $business->getKey(),
            'user_id' => $someoneElse->getKey(),
        ]);

        $foreignAppointment = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $foreignProfile->getKey(),
            'user_id' => $someoneElse->getKey(),
        ]);

        Passport::actingAs($user);

        $this->getJson("/api/client/appointments/{$foreignAppointment->getKey()}")
            ->assertNotFound()
            ->assertJsonPath('message', 'El recurso solicitado no existe.');
    }

    public function test_client_can_cancel_its_own_upcoming_appointment(): void
    {
        $user = $this->makeUser([], RoleName::Client);
        [, $business] = $this->makeBusinessOwner();
        $clientProfile = Client::factory()->create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->copy()->addDays(3)->setTime(10, 30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Passport::actingAs($user);

        $this->patchJson("/api/client/appointments/{$appointment->getKey()}/cancel", [
            'cancel_reason' => 'Se me complicó la semana.',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Cita cancelada correctamente.')
            ->assertJsonPath('data.status.name', AppointmentStatus::Cancelled->value)
            ->assertJsonPath('data.cancel_reason', 'Se me complicó la semana.');

        $this->assertNotNull($appointment->refresh()->cancelled_at);
    }

    public function test_client_cannot_cancel_an_appointment_that_already_happened(): void
    {
        $user = $this->makeUser([], RoleName::Client);
        [, $business] = $this->makeBusinessOwner();
        $clientProfile = Client::factory()->create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $appointment = Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $clientProfile->getKey(),
            'user_id' => $user->getKey(),
            'starts_at' => now()->copy()->subDay()->setTime(10, 0),
            'ends_at' => now()->copy()->subDay()->setTime(10, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        Passport::actingAs($user);

        $this->patchJson("/api/client/appointments/{$appointment->getKey()}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'No puedes cancelar una cita cuya fecha ya pasó.');
    }

    public function test_client_endpoints_are_restricted_to_the_client_role(): void
    {
        $business = $this->makeUser([], RoleName::Business);

        Passport::actingAs($business);

        $this->getJson('/api/client/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para realizar esta acción.');

        $this->getJson('/api/client/appointments')->assertForbidden();
    }

    public function test_client_dashboard_counts_only_active_businesses(): void
    {
        $user = $this->makeUser([], RoleName::Client);

        Business::factory()->count(2)->create();
        Business::factory()->suspended()->create();

        Passport::actingAs($user);

        $this->getJson('/api/client/dashboard')
            ->assertOk()
            ->assertJsonPath('data.businesses.available', 2)
            ->assertJsonPath('data.businesses.registered_in', 0);
    }
}
