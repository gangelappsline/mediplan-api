<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de detalle de un negocio.
 */
#[OA\Schema(
    schema: 'BusinessResponse',
    title: 'Negocio (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Negocio obtenido correctamente.'),
        new OA\Property(property: 'data', ref: Business::class),
    ],
)]
final class BusinessResponse
{
}
