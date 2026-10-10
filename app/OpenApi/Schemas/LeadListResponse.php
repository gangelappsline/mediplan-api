<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta paginada de leads.
 */
#[OA\Schema(
    schema: 'LeadListResponse',
    title: 'Listado de leads',
    type: 'object',
    required: ['message', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Leads obtenidos correctamente.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: Lead::class)),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
)]
final class LeadListResponse
{
}
