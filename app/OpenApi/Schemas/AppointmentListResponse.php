<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta paginada de citas.
 */
#[OA\Schema(
    schema: 'AppointmentListResponse',
    title: 'Listado de citas',
    type: 'object',
    required: ['message', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Citas obtenidas correctamente.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: Appointment::class)),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
)]
final class AppointmentListResponse
{
}
