<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error 404 devuelto cuando el recurso no existe o pertenece a otro negocio.
 */
#[OA\Schema(
    schema: 'NotFoundError',
    title: 'No encontrado',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'El recurso solicitado no existe.'),
    ],
    example: ['message' => 'El recurso solicitado no existe.'],
)]
final class NotFoundError
{
}
