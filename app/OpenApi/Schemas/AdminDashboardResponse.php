<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Respuesta del panel de administración de la plataforma.
 */
#[OA\Schema(
    schema: 'AdminDashboardResponse',
    title: 'Panel de administración',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Panel de administración generado correctamente.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'users',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 128),
                        new OA\Property(property: 'inactive', type: 'integer', example: 4),
                        new OA\Property(property: 'new_last_month', type: 'integer', example: 18),
                        new OA\Property(
                            property: 'by_role',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'business'),
                                    new OA\Property(property: 'label', type: 'string', example: 'Negocio'),
                                    new OA\Property(property: 'total', type: 'integer', example: 22),
                                ],
                            ),
                        ),
                    ],
                ),
                new OA\Property(
                    property: 'businesses',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 22),
                        new OA\Property(property: 'new_last_month', type: 'integer', example: 3),
                        new OA\Property(
                            property: 'by_status',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'active'),
                                    new OA\Property(property: 'label', type: 'string', example: 'Activo'),
                                    new OA\Property(property: 'total', type: 'integer', example: 19),
                                ],
                            ),
                        ),
                    ],
                ),
                new OA\Property(
                    property: 'clients',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 640),
                        new OA\Property(property: 'new_last_month', type: 'integer', example: 71),
                    ],
                ),
                new OA\Property(
                    property: 'leads',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 310),
                        new OA\Property(property: 'open', type: 'integer', example: 96),
                        new OA\Property(property: 'won', type: 'integer', example: 140),
                        new OA\Property(property: 'conversion_rate', type: 'number', format: 'float', example: 58.3),
                    ],
                ),
                new OA\Property(
                    property: 'appointments',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 1520),
                        new OA\Property(property: 'today', type: 'integer', example: 84),
                        new OA\Property(property: 'upcoming', type: 'integer', example: 392),
                        new OA\Property(property: 'completed', type: 'integer', example: 1010),
                    ],
                ),
                new OA\Property(property: 'recent_users', type: 'array', items: new OA\Items(ref: User::class)),
                new OA\Property(property: 'recent_businesses', type: 'array', items: new OA\Items(ref: Business::class)),
            ],
        ),
    ],
)]
final class AdminDashboardResponse
{
}
