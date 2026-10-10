<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta del panel de control de un negocio.
 */
#[OA\Schema(
    schema: 'BusinessDashboardResponse',
    title: 'Panel del negocio',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Panel del negocio generado correctamente.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'business',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Clínica Dental Sonrisa'),
                        new OA\Property(property: 'status', ref: Status::class),
                    ],
                ),
                new OA\Property(
                    property: 'clients',
                    description: 'Resumen del directorio de clientes.',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 42),
                        new OA\Property(property: 'active', type: 'integer', example: 38),
                        new OA\Property(property: 'new_this_month', type: 'integer', example: 5),
                    ],
                ),
                new OA\Property(
                    property: 'leads',
                    description: 'Resumen del embudo de leads.',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 30),
                        new OA\Property(property: 'open', type: 'integer', example: 12),
                        new OA\Property(
                            property: 'by_status',
                            description: 'Conteo por estado (new, contacted, qualified, proposal, won, lost).',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'integer'),
                        ),
                        new OA\Property(property: 'conversion_rate', type: 'number', format: 'float', example: 62.5),
                    ],
                ),
                new OA\Property(
                    property: 'appointments',
                    description: 'Actividad de la agenda.',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'today', description: 'Citas con fecha de hoy, en cualquier estado.', type: 'integer', example: 6),
                        new OA\Property(property: 'next_week', description: 'Citas aún agendadas en los próximos 7 días.', type: 'integer', example: 24),
                        new OA\Property(property: 'completed_this_month', type: 'integer', example: 57),
                        new OA\Property(property: 'cancelled_this_month', type: 'integer', example: 3),
                        new OA\Property(property: 'revenue_this_month', type: 'number', format: 'float', example: 48250.0),
                        new OA\Property(
                            property: 'monthly_activity',
                            description: 'Últimos seis meses.',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'month', type: 'string', example: '2026-10'),
                                    new OA\Property(property: 'label', type: 'string', example: 'octubre 2026'),
                                    new OA\Property(property: 'total', type: 'integer', example: 74),
                                    new OA\Property(property: 'completed', type: 'integer', example: 57),
                                    new OA\Property(property: 'cancelled', type: 'integer', example: 3),
                                ],
                            ),
                        ),
                    ],
                ),
                new OA\Property(property: 'next_appointments', type: 'array', items: new OA\Items(ref: Appointment::class)),
                new OA\Property(property: 'recent_leads', type: 'array', items: new OA\Items(ref: Lead::class)),
                new OA\Property(property: 'recent_clients', type: 'array', items: new OA\Items(ref: Client::class)),
            ],
        ),
    ],
)]
final class BusinessDashboardResponse
{
}
