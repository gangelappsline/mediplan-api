<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta de detalle de una cita.
 */
#[OA\Schema(
    schema: 'AppointmentResponse',
    title: 'Cita (respuesta)',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Cita obtenida correctamente.'),
        new OA\Property(property: 'data', ref: Appointment::class),
    ],
)]
final class AppointmentResponse
{
}
