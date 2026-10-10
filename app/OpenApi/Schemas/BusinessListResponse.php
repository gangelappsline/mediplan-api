<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta paginada de negocios.
 */
#[OA\Schema(
    schema: 'BusinessListResponse',
    title: 'Listado de negocios',
    type: 'object',
    required: ['message', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Negocios obtenidos correctamente.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: Business::class)),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
)]
final class BusinessListResponse
{
}
