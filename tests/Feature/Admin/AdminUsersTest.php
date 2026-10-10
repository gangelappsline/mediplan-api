<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Necesario para emitir tokens en la prueba de login bloqueado.
        app(ClientRepository::class)->createPersonalAccessClient(null, 'MediPlan Test', 'http://localhost');
    }

    public function test_admin_can_list_users_with_roles_and_business(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [$businessOwner] = $this->makeBusinessOwner(['name' => 'Dueño Negocio']);
        $this->makeUser(['name' => 'Cliente Uno'], RoleName::Client);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonPath('message', 'Usuarios obtenidos correctamente.')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'is_active', 'roles' => [['name', 'label']]]]]);

        $this->getJson('/api/admin/users?role=business')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Dueño Negocio')
            ->assertJsonPath('data.0.business.name', 'Dueño Negocio');

        $this->getJson('/api/admin/users?search=Cliente%20Uno')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_filter_inactive_users(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        $this->makeUser(['is_active' => false], RoleName::Client);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/users?status=inactive')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.is_active', false);
    }

    public function test_admin_can_create_a_user_with_any_role(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $this->postJson('/api/admin/users', [
            'name' => 'Nuevo Administrador',
            'email' => 'admin2@mediplan.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'administrador',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Usuario creado correctamente.')
            ->assertJsonPath('data.email', 'admin2@mediplan.com')
            ->assertJsonPath('data.roles.0.name', RoleName::Admin->value);
    }

    public function test_creating_a_business_user_also_creates_its_business(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Ana Torres',
            'email' => 'ana@mediplan.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'negocio',
            'business_name' => 'Clínica Torres',
        ])
            ->assertCreated()
            ->assertJsonPath('data.business.name', 'Clínica Torres');

        $businessId = $response->json('data.business.id');

        $this->assertDatabaseHas('businesses', ['id' => $businessId, 'name' => 'Clínica Torres']);
        $this->assertDatabaseHas('business_settings', ['business_id' => $businessId]);
    }

    public function test_admin_can_update_roles_of_a_user(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        $user = $this->makeUser([], RoleName::Client);

        Passport::actingAs($admin);

        $this->putJson("/api/admin/users/{$user->getKey()}/roles", ['roles' => ['negocio', 'cliente']])
            ->assertOk()
            ->assertJsonPath('message', 'Roles actualizados correctamente.')
            ->assertJsonCount(2, 'data.roles');

        $this->assertTrue($user->hasRole(RoleName::Business));
        $this->assertTrue($user->hasRole(RoleName::Client));
        $this->assertFalse($user->hasRole(RoleName::Admin));
    }

    public function test_an_admin_cannot_remove_its_own_admin_role(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->getKey()}/roles", ['roles' => ['cliente']])
            ->assertStatus(422)
            ->assertJsonPath('errors.roles.0', 'No puedes quitarte a ti mismo el rol de administrador.');

        $this->assertTrue($admin->hasRole(RoleName::Admin));
    }

    public function test_deactivating_a_user_blocks_the_login(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        $user = $this->makeUser(['email' => 'bloqueado@mediplan.com'], RoleName::Client);
        $user->forceFill(['password' => 'password123'])->save();

        // El login funciona mientras la cuenta está activa.
        $this->postJson('/api/login', [
            'email' => 'bloqueado@mediplan.com',
            'password' => 'password123',
        ])->assertOk();

        Passport::actingAs($admin);

        $this->patchJson("/api/admin/users/{$user->getKey()}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('message', 'Cuenta desactivada correctamente.')
            ->assertJsonPath('data.is_active', false);

        $this->postJson('/api/login', [
            'email' => 'bloqueado@mediplan.com',
            'password' => 'password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Tu cuenta está desactivada. Contacta al administrador de la plataforma.');
    }

    public function test_an_admin_cannot_deactivate_or_delete_itself(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->getKey()}/status", ['is_active' => false])
            ->assertStatus(422)
            ->assertJsonPath('errors.is_active.0', 'No puedes desactivar tu propia cuenta.');

        $this->deleteJson("/api/admin/users/{$admin->getKey()}")
            ->assertStatus(422)
            ->assertJsonPath('errors.user.0', 'No puedes eliminar tu propia cuenta.');

        $this->assertDatabaseHas('users', ['id' => $admin->getKey()]);
    }

    public function test_deleting_a_user_removes_its_business_and_data(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [$owner, $business] = $this->makeBusinessOwner();

        Client::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$owner->getKey()}")
            ->assertOk()
            ->assertJsonPath('message', 'Usuario eliminado correctamente.');

        $this->assertDatabaseMissing('users', ['id' => $owner->getKey()]);
        $this->assertDatabaseMissing('businesses', ['id' => $business->getKey()]);
        $this->assertDatabaseMissing('clients', ['business_id' => $business->getKey()]);
    }

    public function test_user_endpoints_require_the_admin_role(): void
    {
        $clientUser = $this->makeUser([], RoleName::Client);

        Passport::actingAs($clientUser);

        $this->getJson('/api/admin/users')->assertForbidden();
        $this->postJson('/api/admin/users', [])->assertForbidden();
    }

    public function test_admin_can_list_roles_with_their_user_count(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        $this->makeUser([], RoleName::Client);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonPath('message', 'Roles obtenidos correctamente.')
            ->assertJsonStructure(['data' => [['id', 'name', 'label', 'description', 'users_count']]]);
    }

    public function test_admin_can_list_leads_from_every_business(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);
        [, $first] = $this->makeBusinessOwner();
        [, $second] = $this->makeBusinessOwner();

        Lead::factory()->count(2)->create(['business_id' => $first->getKey()]);
        Lead::factory()->status(LeadStatus::Won)->create(['business_id' => $second->getKey()]);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/leads')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/admin/leads?business_id='.$second->getKey())
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/admin/leads?status=won')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }
}
