<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error exacto para solicitudes sin un token Passport válido.
 */
#[OA\Schema(
    schema: 'UnauthenticatedError',
    title: 'No autenticado',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'No autenticado.',
        ),
    ],
)]
final class UnauthenticatedError
{
}
