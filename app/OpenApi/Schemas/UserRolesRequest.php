<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para reemplazar los roles de un usuario.
 */
#[OA\Schema(
    schema: 'UserRolesRequest',
    title: 'Roles del usuario (cuerpo de la petición)',
    type: 'object',
    required: ['roles'],
    properties: [
        new OA\Property(
            property: 'roles',
            type: 'array',
            minItems: 1,
            items: new OA\Items(
                type: 'string',
                enum: ['client', 'business', 'admin', 'cliente', 'negocio', 'administrador'],
            ),
            example: ['business', 'client'],
        ),
    ],
)]
final class UserRolesRequest
{
}
