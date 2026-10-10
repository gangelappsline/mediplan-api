<?php

namespace Tests\Feature\Business;

use App\Enums\BusinessStatus;
use App\Enums\RoleName;
use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class ProfileAndSettingsTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_business_can_read_its_profile(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->getJson('/api/business/profile')
            ->assertOk()
            ->assertJsonPath('message', 'Negocio obtenido correctamente.')
            ->assertJsonPath('data.id', $business->getKey())
            ->assertJsonPath('data.status.name', BusinessStatus::Active->value)
            ->assertJsonPath('data.owner.email', $owner->email)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'phone', 'status' => ['name', 'label'], 'owner']]);
    }

    public function test_business_can_update_its_profile(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->putJson('/api/business/profile', [
            'name' => 'Clínica Dental Sonrisa',
            'description' => 'Consultorio dental integral.',
            'phone' => '5512345678',
            'city' => 'Ciudad de México',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Negocio actualizado correctamente.')
            ->assertJsonPath('data.name', 'Clínica Dental Sonrisa')
            ->assertJsonPath('data.city', 'Ciudad de México');

        $this->assertDatabaseHas('businesses', ['name' => 'Clínica Dental Sonrisa', 'city' => 'Ciudad de México']);
    }

    public function test_reading_the_settings_creates_the_defaults(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        // Sin configuración previa.
        $business->setting()->delete();
        $this->assertSame(0, BusinessSetting::query()->count());

        Passport::actingAs($owner);

        $response = $this->getJson('/api/business/settings')
            ->assertOk()
            ->assertJsonPath('message', 'Configuración obtenida correctamente.')
            ->assertJsonPath('data.business_id', $business->getKey())
            ->assertJsonPath('data.appointment_duration_minutes', 30)
            ->assertJsonPath('data.slot_interval_minutes', 30)
            ->assertJsonPath('data.timezone', 'America/Mexico_City')
            ->assertJsonPath('data.allow_online_booking', true)
            ->assertJsonStructure(['data' => ['working_hours' => ['monday' => ['open', 'close', 'closed']]]]);

        $this->assertSame(1, BusinessSetting::query()->count());
        $this->assertSame('sunday', array_key_last($response->json('data.working_hours')));
    }

    public function test_business_can_update_its_settings(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->putJson('/api/business/settings', [
            'appointment_duration_minutes' => 45,
            'auto_confirm_appointments' => true,
            'working_hours' => [
                'monday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Configuración actualizada correctamente.')
            ->assertJsonPath('data.appointment_duration_minutes', 45)
            ->assertJsonPath('data.auto_confirm_appointments', true)
            ->assertJsonPath('data.working_hours.monday.open', '08:00');

        $this->assertDatabaseHas('business_settings', ['appointment_duration_minutes' => 45]);
    }

    public function test_settings_validation_rejects_unknown_days_and_bad_ranges(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->putJson('/api/business/settings', [
            'working_hours' => [
                'lunes' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('working_hours');

        $this->putJson('/api/business/settings', [
            'working_hours' => [
                'tuesday' => ['open' => '18:00', 'close' => '09:00', 'closed' => false],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.working_hours.0',
                'La hora de cierre debe ser posterior a la hora de apertura en "tuesday".',
            );
    }

    public function test_settings_validation_rejects_impossible_durations(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->putJson('/api/business/settings', [
            'appointment_duration_minutes' => 1,
            'currency' => 'PESOS',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.appointment_duration_minutes.0', 'La duración mínima de una cita es de 5 minutos.')
            ->assertJsonPath('errors.currency.0', 'La moneda debe tener exactamente 3 caracteres (por ejemplo MXN).');
    }

    public function test_profile_endpoints_require_the_business_role(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $this->getJson('/api/business/profile')->assertForbidden();
        $this->getJson('/api/business/settings')->assertForbidden();
    }

    public function test_a_business_user_without_a_business_gets_a_clear_error(): void
    {
        // Usuario con rol negocio pero sin ficha de negocio creada.
        $owner = $this->makeUser([], RoleName::Business);

        Passport::actingAs($owner);

        $this->getJson('/api/business/dashboard')
            ->assertNotFound()
            ->assertJsonPath('message', 'Tu usuario todavía no tiene un negocio asociado.');
    }
}
