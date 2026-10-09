<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     *
     * Role names are stored in English; labels are shown in Spanish.
     */
    public function run(): void
    {
        $roles = [
            RoleName::Client->value => 'Usuario cliente que consume los servicios de los negocios.',
            RoleName::Business->value => 'Negocio que ofrece sus servicios dentro de la plataforma.',
            RoleName::Admin->value => 'Administrador con acceso total a la plataforma.',
        ];

        foreach ($roles as $name => $description) {
            Role::query()->updateOrCreate(
                ['name' => $name],
                [
                    'label' => RoleName::from($name)->label(),
                    'description' => $description,
                ],
            );
        }
    }
}
