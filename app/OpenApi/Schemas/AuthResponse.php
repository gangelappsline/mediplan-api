<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta compartida por registro e inicio de sesión.
 */
#[OA\Schema(
    schema: 'AuthResponse',
    title: 'Respuesta de autenticación',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Sesión iniciada correctamente.',
        ),
        new OA\Property(
            property: 'data',
            ref: AuthPayload::class,
        ),
    ],
)]
final class AuthResponse
{
}
