<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de GET /me.
 */
#[OA\Schema(
    schema: 'MeResponse',
    title: 'Usuario autenticado',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Usuario autenticado.',
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['user'],
            properties: [
                new OA\Property(
                    property: 'user',
                    ref: User::class,
                ),
            ],
        ),
    ],
)]
final class MeResponse
{
}
