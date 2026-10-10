<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para actualizar la configuración operativa del negocio.
 */
#[OA\Schema(
    schema: 'BusinessSettingsRequest',
    title: 'Configuración del negocio (cuerpo de la petición)',
    type: 'object',
    properties: [
        new OA\Property(property: 'timezone', type: 'string', example: 'America/Mexico_City'),
        new OA\Property(property: 'appointment_duration_minutes', type: 'integer', minimum: 5, maximum: 480, example: 45),
        new OA\Property(property: 'slot_interval_minutes', type: 'integer', minimum: 5, maximum: 240, example: 15),
        new OA\Property(property: 'min_notice_minutes', type: 'integer', minimum: 0, maximum: 10080, example: 120),
        new OA\Property(property: 'max_advance_days', type: 'integer', minimum: 1, maximum: 365, example: 60),
        new OA\Property(property: 'auto_confirm_appointments', type: 'boolean', example: true),
        new OA\Property(property: 'allow_online_booking', type: 'boolean', example: true),
        new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'MXN'),
        new OA\Property(
            property: 'working_hours',
            description: 'Horario por día (monday..sunday). Cada día acepta open, close y closed.',
            type: 'object',
            example: [
                'monday' => ['open' => '09:00', 'close' => '19:00', 'closed' => false],
                'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
            ],
            additionalProperties: new OA\AdditionalProperties(
                type: 'object',
                properties: [
                    new OA\Property(property: 'open', type: 'string', example: '09:00'),
                    new OA\Property(property: 'close', type: 'string', example: '19:00'),
                    new OA\Property(property: 'closed', type: 'boolean', example: false),
                ],
            ),
        ),
    ],
)]
final class BusinessSettingsRequest
{
}
