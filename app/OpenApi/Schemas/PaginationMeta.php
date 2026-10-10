<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Metadatos de paginación incluidos en las respuestas de listado.
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Metadatos de paginación',
    type: 'object',
    required: ['current_page', 'last_page', 'per_page', 'total'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 42),
    ],
)]
final class PaginationMeta
{
}
