<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Configuración operativa de un negocio.
 */
#[OA\Schema(
    schema: 'BusinessSettings',
    title: 'Configuración del negocio',
    type: 'object',
    required: ['timezone', 'appointment_duration_minutes', 'working_hours'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'business_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'timezone', type: 'string', example: 'America/Mexico_City'),
        new OA\Property(property: 'appointment_duration_minutes', type: 'integer', example: 30),
        new OA\Property(property: 'slot_interval_minutes', type: 'integer', example: 30),
        new OA\Property(property: 'min_notice_minutes', type: 'integer', description: 'Anticipación mínima para reservar.', example: 60),
        new OA\Property(property: 'max_advance_days', type: 'integer', example: 30),
        new OA\Property(
            property: 'working_hours',
            description: 'Horario por día. Cada día tiene open, close y closed.',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'object',
                properties: [
                    new OA\Property(property: 'open', type: 'string', example: '09:00'),
                    new OA\Property(property: 'close', type: 'string', example: '18:00'),
                    new OA\Property(property: 'closed', type: 'boolean', example: false),
                ],
            ),
        ),
        new OA\Property(property: 'auto_confirm_appointments', type: 'boolean', example: false),
        new OA\Property(property: 'allow_online_booking', type: 'boolean', example: true),
        new OA\Property(property: 'currency', type: 'string', example: 'MXN'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class BusinessSettings
{
}
