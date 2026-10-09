<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default administrator and demo users.
     */
    public function run(): void
    {
        $this->createUser(
            name: 'Administrador',
            email: 'admin@mediplan.com',
            password: 'password',
            roles: [RoleName::Admin],
        );

        $this->createUser(
            name: 'Cliente de prueba',
            email: 'cliente@mediplan.com',
            password: 'password',
            roles: [RoleName::Client],
        );

        $this->createUser(
            name: 'Negocio de prueba',
            email: 'negocio@mediplan.com',
            password: 'password',
            roles: [RoleName::Business],
        );
    }

    /**
     * @param  array<int, RoleName>  $roles
     */
    protected function createUser(string $name, string $email, string $password, array $roles): void
    {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(...$roles);
    }
}
