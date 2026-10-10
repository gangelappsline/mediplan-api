<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de detalle de un lead.
 */
#[OA\Schema(
    schema: 'LeadResponse',
    title: 'Lead (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Lead obtenido correctamente.'),
        new OA\Property(property: 'data', ref: Lead::class),
    ],
)]
final class LeadResponse
{
}
