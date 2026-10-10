<?php

namespace Tests\Feature\Admin;

use App\Enums\BusinessStatus;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class AdminBusinessesTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_admin_can_list_businesses_with_counters(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [, $business] = $this->makeBusinessOwner(['name' => 'Clínica Norte']);
        [, $other] = $this->makeBusinessOwner(['name' => 'Spa Sur']);

        Client::factory()->count(2)->create(['business_id' => $business->getKey()]);
        Lead::factory()->create(['business_id' => $business->getKey()]);
        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => Client::factory()->create(['business_id' => $business->getKey()])->getKey(),
        ]);

        Passport::actingAs($admin);

        $response = $this->getJson('/api/admin/businesses')
            ->assertOk()
            ->assertJsonPath('message', 'Negocios obtenidos correctamente.')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'status' => ['name', 'label'], 'owner' => ['id', 'email'], 'clients_count', 'leads_count', 'appointments_count']]]);

        $clinica = collect($response->json('data'))->firstWhere('name', 'Clínica Norte');

        $this->assertSame(3, $clinica['clients_count']);
        $this->assertSame(1, $clinica['leads_count']);
        $this->assertSame(1, $clinica['appointments_count']);
        $this->assertNotNull($other->getKey());
    }

    public function test_admin_can_filter_businesses_by_status_and_search(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        [, $active] = $this->makeBusinessOwner(['name' => 'Dental Activo']);
        Business::factory()->pending()->create(['name' => 'Dental Pendiente']);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/businesses?status=pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Dental Pendiente');

        $this->getJson('/api/admin/businesses?search=Activo')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $active->getKey());
    }

    public function test_admin_can_update_a_business(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [, $business] = $this->makeBusinessOwner();

        Passport::actingAs($admin);

        $this->putJson("/api/admin/businesses/{$business->getKey()}", [
            'name' => 'Clínica Renombrada',
            'city' => 'Guadalajara',
            'description' => 'Nueva descripción',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Negocio actualizado correctamente.')
            ->assertJsonPath('data.name', 'Clínica Renombrada')
            ->assertJsonPath('data.city', 'Guadalajara');

        $this->assertDatabaseHas('businesses', ['id' => $business->getKey(), 'name' => 'Clínica Renombrada']);
    }

    public function test_admin_can_suspend_and_reactivate_a_business(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [, $business] = $this->makeBusinessOwner();

        Passport::actingAs($admin);

        $this->patchJson("/api/admin/businesses/{$business->getKey()}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.status.name', BusinessStatus::Suspended->value)
            ->assertJsonPath('data.status.label', BusinessStatus::Suspended->label());

        $this->assertDatabaseHas('businesses', ['id' => $business->getKey(), 'status' => 'suspended']);

        $this->patchJson("/api/admin/businesses/{$business->getKey()}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status.name', BusinessStatus::Active->value);
    }

    public function test_business_status_must_be_a_known_value(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [, $business] = $this->makeBusinessOwner();

        Passport::actingAs($admin);

        $this->patchJson("/api/admin/businesses/{$business->getKey()}/status", ['status' => 'eliminado'])
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'El estado no es válido. Valores permitidos: pending, active, suspended.');
    }

    public function test_admin_can_delete_a_business_and_keep_the_owner_account(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [$owner, $business] = $this->makeBusinessOwner();

        $client = Client::factory()->create(['business_id' => $business->getKey()]);
        Lead::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($admin);

        $this->deleteJson("/api/admin/businesses/{$business->getKey()}")
            ->assertOk()
            ->assertJsonPath('message', 'Negocio eliminado correctamente.');

        $this->assertDatabaseMissing('businesses', ['id' => $business->getKey()]);
        $this->assertDatabaseMissing('clients', ['id' => $client->getKey()]);
        $this->assertDatabaseMissing('leads', ['business_id' => $business->getKey()]);
        $this->assertDatabaseHas('users', ['id' => $owner->getKey()]);
    }

    public function test_business_admin_endpoints_require_the_admin_role(): void
    {
        [$businessOwner] = $this->makeBusinessOwner();

        Passport::actingAs($businessOwner);

        $this->getJson('/api/admin/businesses')->assertForbidden();
        $this->getJson('/api/admin/leads')->assertForbidden();
        $this->getJson('/api/admin/roles')->assertForbidden();
    }
}
