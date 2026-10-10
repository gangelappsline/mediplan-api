<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error 403 devuelto por el middleware de roles.
 */
#[OA\Schema(
    schema: 'ForbiddenError',
    title: 'Sin permisos',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'No tienes permiso para realizar esta acción.'),
    ],
    example: ['message' => 'No tienes permiso para realizar esta acción.'],
)]
final class ForbiddenError
{
}
