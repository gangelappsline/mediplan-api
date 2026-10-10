<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta paginada de usuarios.
 */
#[OA\Schema(
    schema: 'UserListResponse',
    title: 'Listado de usuarios',
    type: 'object',
    required: ['message', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Usuarios obtenidos correctamente.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: User::class)),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
)]
final class UserListResponse
{
}
