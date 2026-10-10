<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de la configuración del negocio.
 */
#[OA\Schema(
    schema: 'BusinessSettingsResponse',
    title: 'Configuración del negocio (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Configuración obtenida correctamente.'),
        new OA\Property(property: 'data', ref: BusinessSettings::class),
    ],
)]
final class BusinessSettingsResponse
{
}
