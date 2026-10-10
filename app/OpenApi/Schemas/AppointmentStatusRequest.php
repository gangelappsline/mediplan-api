<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para cambiar el estado de una cita.
 */
#[OA\Schema(
    schema: 'AppointmentStatusRequest',
    title: 'Estado de la cita (cuerpo de la petición)',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['confirmed', 'completed', 'cancelled', 'no_show'], example: 'cancelled'),
        new OA\Property(property: 'cancel_reason', description: 'Obligatorio cuando el estado es cancelled.', type: 'string', nullable: true, maxLength: 255, example: 'El cliente reprogramó.'),
    ],
)]
final class AppointmentStatusRequest
{
}
