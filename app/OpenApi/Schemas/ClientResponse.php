<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de detalle de un cliente.
 */
#[OA\Schema(
    schema: 'ClientResponse',
    title: 'Cliente (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Cliente obtenido correctamente.'),
        new OA\Property(property: 'data', ref: Client::class),
    ],
)]
final class ClientResponse
{
}
