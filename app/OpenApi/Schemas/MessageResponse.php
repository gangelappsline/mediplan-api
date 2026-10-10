<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de operaciones que solo retornan un mensaje.
 */
#[OA\Schema(
    schema: 'MessageResponse',
    title: 'Respuesta con mensaje',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            description: 'Mensaje informativo para el consumidor de la API.',
            type: 'string',
            example: 'Sesión cerrada correctamente.',
        ),
    ],
)]
final class MessageResponse
{
}
