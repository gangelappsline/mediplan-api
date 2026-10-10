<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta paginada de clientes.
 */
#[OA\Schema(
    schema: 'ClientListResponse',
    title: 'Listado de clientes',
    type: 'object',
    required: ['message', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Clientes obtenidos correctamente.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: Client::class)),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
)]
final class ClientListResponse
{
}
