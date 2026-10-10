<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de detalle de un usuario.
 */
#[OA\Schema(
    schema: 'UserResponse',
    title: 'Usuario (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Usuario obtenido correctamente.'),
        new OA\Property(property: 'data', ref: User::class),
    ],
)]
final class UserResponse
{
}
