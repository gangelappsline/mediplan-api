<?php

namespace Tests\Feature\Business;

use App\Enums\ClientStatus;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_business_can_list_its_clients_paginated(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();

        Client::factory()->count(4)->create(['business_id' => $business->getKey()]);
        Client::factory()->count(2)->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $this->getJson('/api/business/clients?per_page=2')
            ->assertOk()
            ->assertJsonPath('message', 'Clientes obtenidos correctamente.')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'business_id', 'name', 'email', 'status' => ['name', 'label'], 'appointments_count']],
            ]);
    }

    public function test_client_list_supports_search_and_status_filters(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Client::factory()->create(['business_id' => $business->getKey(), 'name' => 'María López']);
        Client::factory()->inactive()->create(['business_id' => $business->getKey(), 'name' => 'Juan Pérez']);

        Passport::actingAs($owner);

        $this->getJson('/api/business/clients?search=María')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'María López');

        $this->getJson('/api/business/clients?status=inactive')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Juan Pérez')
            ->assertJsonPath('data.0.status.name', ClientStatus::Inactive->value);
    }

    public function test_business_can_create_a_client(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $response = $this->postJson('/api/business/clients', [
            'name' => 'María López',
            'email' => 'maria@example.com',
            'phone' => '5512345678',
            'birth_date' => '1990-04-12',
            'notes' => 'Prefiere citas por la tarde.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Cliente creado correctamente.')
            ->assertJsonPath('data.name', 'María López')
            ->assertJsonPath('data.business_id', $business->getKey())
            ->assertJsonPath('data.status.name', ClientStatus::Active->value)
            ->assertJsonPath('data.status.label', ClientStatus::Active->label());

        $this->assertDatabaseHas('clients', [
            'business_id' => $business->getKey(),
            'email' => 'maria@example.com',
        ]);
    }

    public function test_client_email_must_be_unique_per_business(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();

        Client::factory()->create(['business_id' => $otherBusiness->getKey(), 'email' => 'repetido@example.com']);

        Passport::actingAs($owner);

        // El mismo correo registrado en otro negocio sí es válido.
        $this->postJson('/api/business/clients', [
            'name' => 'Cliente nuevo',
            'email' => 'repetido@example.com',
        ])->assertCreated();

        // Dentro del mismo negocio se rechaza con mensaje en español.
        $this->postJson('/api/business/clients', [
            'name' => 'Cliente duplicado',
            'email' => 'repetido@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Ya existe un cliente con este correo electrónico en tu negocio.');

        $this->assertSame(1, Client::query()->where('business_id', $business->getKey())->count());
    }

    public function test_client_creation_validates_required_fields_in_spanish(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->postJson('/api/business/clients', ['email' => 'no-es-un-correo'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'El nombre del cliente es obligatorio.')
            ->assertJsonPath('errors.email.0', 'El correo electrónico debe ser una dirección válida.');
    }

    public function test_business_can_update_and_delete_a_client(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($owner);

        $this->putJson("/api/business/clients/{$client->getKey()}", [
            'name' => 'Nombre actualizado',
            'status' => ClientStatus::Inactive->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre actualizado')
            ->assertJsonPath('data.status.name', ClientStatus::Inactive->value);

        $this->assertDatabaseHas('clients', ['id' => $client->getKey(), 'name' => 'Nombre actualizado']);

        $this->deleteJson("/api/business/clients/{$client->getKey()}")
            ->assertOk()
            ->assertJsonPath('message', 'Cliente eliminado correctamente.');

        $this->assertDatabaseMissing('clients', ['id' => $client->getKey()]);
    }

    public function test_a_business_cannot_read_or_modify_another_business_client(): void
    {
        [$owner] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();
        $foreignClient = Client::factory()->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $this->getJson("/api/business/clients/{$foreignClient->getKey()}")
            ->assertNotFound()
            ->assertJsonPath('message', 'El recurso solicitado no existe.');

        $this->putJson("/api/business/clients/{$foreignClient->getKey()}", ['name' => 'Hack'])
            ->assertNotFound();

        $this->deleteJson("/api/business/clients/{$foreignClient->getKey()}")
            ->assertNotFound();

        $this->assertDatabaseHas('clients', ['id' => $foreignClient->getKey()]);
    }

    public function test_client_endpoints_are_restricted_to_the_business_role(): void
    {
        $clientUser = $this->makeUser([], RoleName::Client);

        Passport::actingAs($clientUser);

        $this->getJson('/api/business/clients')->assertForbidden();
        $this->postJson('/api/business/clients', ['name' => 'X'])->assertForbidden();
    }

    public function test_deleting_a_client_removes_its_appointments(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
        ]);

        Passport::actingAs($owner);

        $this->deleteJson("/api/business/clients/{$client->getKey()}")->assertOk();

        $this->assertDatabaseMissing('appointments', ['client_id' => $client->getKey()]);
    }
}
