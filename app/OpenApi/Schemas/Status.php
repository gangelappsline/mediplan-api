<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Par valor/etiqueta usado para los estados de la API.
 */
#[OA\Schema(
    schema: 'Status',
    title: 'Estado',
    description: 'Valor guardado en base de datos (name, en inglés) y etiqueta para mostrar (label, en español).',
    type: 'object',
    required: ['name', 'label'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'active'),
        new OA\Property(property: 'label', type: 'string', example: 'Activo'),
    ],
)]
final class Status
{
}
