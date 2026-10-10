<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de la agenda agrupada por día, pensada para calendarios.
 */
#[OA\Schema(
    schema: 'AgendaResponse',
    title: 'Agenda por día',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Agenda obtenida correctamente.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'from', type: 'string', format: 'date', example: '2026-10-01'),
                new OA\Property(property: 'to', type: 'string', format: 'date', example: '2026-10-31'),
                new OA\Property(property: 'total', type: 'integer', example: 96),
                new OA\Property(
                    property: 'days',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-10-12'),
                            new OA\Property(property: 'label', type: 'string', example: 'lunes 12 de octubre'),
                            new OA\Property(property: 'total', type: 'integer', example: 8),
                            new OA\Property(property: 'appointments', type: 'array', items: new OA\Items(ref: Appointment::class)),
                        ],
                    ),
                ),
            ],
        ),
    ],
)]
final class AgendaResponse
{
}
