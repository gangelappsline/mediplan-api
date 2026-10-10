<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error exacto de POST /login cuando las credenciales no coinciden.
 */
#[OA\Schema(
    schema: 'InvalidCredentialsError',
    title: 'Credenciales incorrectas',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Las credenciales proporcionadas son incorrectas.',
        ),
    ],
)]
final class InvalidCredentialsError
{
}
