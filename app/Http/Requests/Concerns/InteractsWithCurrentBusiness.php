<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;

/**
 * Expone el negocio del usuario autenticado a los FormRequest.
 */
trait InteractsWithCurrentBusiness
{
    /**
     * Identificador del negocio del usuario autenticado.
     */
    protected function currentBusinessId(): ?int
    {
        /** @var User|null $user */
        $user = $this->user();

        $id = $user?->business?->getKey();

        return $id === null ? null : (int) $id;
    }
}
