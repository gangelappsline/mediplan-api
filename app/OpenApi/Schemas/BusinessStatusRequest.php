<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para cambiar el estado de un negocio desde administración.
 */
#[OA\Schema(
    schema: 'BusinessStatusRequest',
    title: 'Estado del negocio (cuerpo de la petición)',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'active', 'suspended'], example: 'suspended'),
    ],
)]
final class BusinessStatusRequest
{
}
