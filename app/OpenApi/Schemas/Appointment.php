<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cita de la agenda de un negocio.
 */
#[OA\Schema(
    schema: 'Appointment',
    title: 'Cita',
    type: 'object',
    required: ['id', 'business_id', 'title', 'starts_at', 'ends_at', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'business_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Consulta inicial'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', example: '2026-10-12T16:00:00.000000Z'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', example: '2026-10-12T16:30:00.000000Z'),
        new OA\Property(
            property: 'status',
            description: 'Estado de la cita. is_booked indica si sigue ocupando la agenda.',
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'confirmed'),
                new OA\Property(property: 'label', type: 'string', example: 'Confirmada'),
                new OA\Property(property: 'is_booked', type: 'boolean', example: true),
            ],
        ),
        new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, example: 850.0),
        new OA\Property(property: 'client', nullable: true, ref: Client::class),
        new OA\Property(property: 'business', nullable: true, ref: Business::class),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'cancel_reason', type: 'string', nullable: true, example: 'El cliente reprogramó.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class Appointment
{
}
