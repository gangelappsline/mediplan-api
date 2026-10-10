<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta con los roles disponibles en la plataforma.
 */
#[OA\Schema(
    schema: 'RoleListResponse',
    title: 'Listado de roles',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Roles obtenidos correctamente.'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'client'),
                    new OA\Property(property: 'label', type: 'string', example: 'Cliente'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'users_count', type: 'integer', nullable: true, example: 25),
                ],
            ),
        ),
    ],
)]
final class RoleListResponse
{
}
