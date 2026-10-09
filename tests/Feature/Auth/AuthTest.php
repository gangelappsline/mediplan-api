<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Passport requires a personal access client to issue tokens.
        app(ClientRepository::class)->createPersonalAccessGrantClient('MediPlan Test', 'users');
    }

    public function test_user_can_register_as_client(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cliente',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Usuario registrado correctamente.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'juan@example.com')
            ->assertJsonPath('data.user.roles.0.name', RoleName::Client->value)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'roles'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);

        /** @var User $user */
        $user = User::query()->where('email', 'juan@example.com')->first();

        $this->assertTrue($user->hasRole(RoleName::Client));
        $this->assertTrue($user->hasRole('cliente'));
        $this->assertFalse($user->hasRole(RoleName::Admin));
    }

    public function test_user_can_register_as_business(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Cafetería Central',
            'email' => 'contacto@cafeteria.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'negocio',
        ]);

        $response->assertCreated();

        /** @var User $user */
        $user = User::query()->where('email', 'contacto@cafeteria.com')->first();

        $this->assertTrue($user->hasRole(RoleName::Business));
    }

    public function test_user_cannot_register_as_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'administrador',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_register_validates_input_in_spanish(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role'])
            ->assertJsonPath('errors.name.0', 'El nombre es obligatorio.')
            ->assertJsonPath('errors.email.0', 'El correo electrónico es obligatorio.')
            ->assertJsonPath('errors.password.0', 'La contraseña es obligatoria.')
            ->assertJsonPath('errors.role.0', 'El rol es obligatorio.');
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicado@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Otro Usuario',
            'email' => 'duplicado@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cliente',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Este correo electrónico ya está registrado.');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);
        $user->assignRole(RoleName::Client);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Sesión iniciada correctamente.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.roles.0.name', RoleName::Client->value);

        // The issued token must grant access to protected routes.
        $this->getJson('/api/me', [
            'Authorization' => 'Bearer '.$response->json('data.token'),
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'login@example.com');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);
        $user->assignRole(RoleName::Client);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'contraseña-incorrecta',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Las credenciales proporcionadas son incorrectas.');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['email' => 'logout@example.com']);
        $user->assignRole(RoleName::Client);

        $token = $user->createToken('auth_token')->accessToken;

        $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked' => true,
        ]);

        // The revoked token must no longer work.
        $this->getJson('/api/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'yo@example.com']);
        $user->assignRole(RoleName::Business);

        $token = $user->createToken('auth_token')->accessToken;

        $this->getJson('/api/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', 'Usuario autenticado.')
            ->assertJsonPath('data.user.email', 'yo@example.com')
            ->assertJsonPath('data.user.roles.0.name', RoleName::Business->value);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }

    public function test_role_middleware_allows_users_with_the_role(): void
    {
        Route::middleware(['auth:api', 'role:admin'])->get('/_test-admin-only', fn () => [
            'message' => 'ok',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->getJson('/_test-admin-only', [
            'Authorization' => 'Bearer '.$admin->createToken('auth_token')->accessToken,
        ])->assertOk();
    }

    public function test_role_middleware_blocks_users_without_the_role(): void
    {
        Route::middleware(['auth:api', 'role:admin'])->get('/_test-admin-only', fn () => [
            'message' => 'ok',
        ]);

        $client = User::factory()->create();
        $client->assignRole(RoleName::Client);

        $this->getJson('/_test-admin-only', [
            'Authorization' => 'Bearer '.$client->createToken('auth_token')->accessToken,
        ])->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para realizar esta acción.');
    }
}
