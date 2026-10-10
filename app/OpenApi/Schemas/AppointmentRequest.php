<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para crear o actualizar una cita de la agenda.
 */
#[OA\Schema(
    schema: 'AppointmentRequest',
    title: 'Cita (cuerpo de la petición)',
    type: 'object',
    required: ['client_id', 'title', 'starts_at', 'ends_at'],
    properties: [
        new OA\Property(property: 'client_id', description: 'Cliente del negocio. Debe pertenecer a tu directorio.', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 180, example: 'Consulta inicial'),
        new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', example: '2026-10-12T16:00:00.000000Z'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', example: '2026-10-12T16:30:00.000000Z'),
        new OA\Property(property: 'status', type: 'string', enum: ['scheduled', 'confirmed'], default: 'scheduled', example: 'scheduled'),
        new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, example: 850),
    ],
)]
final class AppointmentRequest
{
}
