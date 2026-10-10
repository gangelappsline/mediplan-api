<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta del panel de control de un usuario cliente.
 */
#[OA\Schema(
    schema: 'ClientDashboardResponse',
    title: 'Panel del cliente',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Panel del cliente generado correctamente.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 2),
                        new OA\Property(property: 'name', type: 'string', example: 'Cliente de prueba'),
                    ],
                ),
                new OA\Property(
                    property: 'appointments',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'upcoming_count', type: 'integer', example: 3),
                        new OA\Property(property: 'completed', type: 'integer', example: 9),
                        new OA\Property(property: 'cancelled', type: 'integer', example: 1),
                        new OA\Property(property: 'total', type: 'integer', example: 13),
                        new OA\Property(property: 'next', description: 'Próxima cita, o null si no hay.', nullable: true, ref: Appointment::class),
                    ],
                ),
                new OA\Property(property: 'upcoming_appointments', type: 'array', items: new OA\Items(ref: Appointment::class)),
                new OA\Property(
                    property: 'businesses',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'registered_in', description: 'Negocios donde la cuenta está dada de alta como cliente.', type: 'integer', example: 2),
                        new OA\Property(property: 'available', description: 'Negocios activos en la plataforma.', type: 'integer', example: 14),
                    ],
                ),
            ],
        ),
    ],
)]
final class ClientDashboardResponse
{
}
