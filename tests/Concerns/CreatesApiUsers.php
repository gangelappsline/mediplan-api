<?php

namespace Tests\Concerns;

use App\Enums\RoleName;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\User;

/**
 * Auxiliares para crear usuarios con roles y negocios en las pruebas.
 */
trait CreatesApiUsers
{
    /**
     * Crea un usuario y le asigna los roles indicados.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUser(array $attributes = [], RoleName ...$roles): User
    {
        /** @var User $user */
        $user = User::factory()->create($attributes);

        if ($roles !== []) {
            $user->syncRoles(...$roles);
        }

        return $user;
    }

    /**
     * Crea un usuario con rol negocio, su negocio y su configuración.
     *
     * @return array{0: User, 1: Business}
     */
    protected function makeBusinessOwner(array $attributes = []): array
    {
        $owner = $this->makeUser($attributes, RoleName::Business);

        /** @var Business $business */
        $business = Business::factory()->create([
            'user_id' => $owner->getKey(),
            'name' => $owner->name,
            'email' => $owner->email,
        ]);

        $business->setting()->create(BusinessSetting::defaults());

        $owner->setRelation('business', $business);

        return [$owner, $business];
    }
}
