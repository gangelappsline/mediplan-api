<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Rol que se incluye dentro del recurso de usuario.
 */
#[OA\Schema(
    schema: 'Role',
    title: 'Rol',
    description: 'Rol asignado al usuario. El nombre se almacena en inglés y la etiqueta se presenta en español.',
    type: 'object',
    required: ['name', 'label'],
    properties: [
        new OA\Property(
            property: 'name',
            description: 'Valor estable para lógica de negocio y autorización.',
            type: 'string',
            enum: ['client', 'business', 'admin'],
            example: 'client',
        ),
        new OA\Property(
            property: 'label',
            description: 'Etiqueta legible para mostrar en el frontend.',
            type: 'string',
            example: 'Cliente',
        ),
    ],
    example: [
        'name' => 'client',
        'label' => 'Cliente',
    ],
)]
final class Role
{
}
