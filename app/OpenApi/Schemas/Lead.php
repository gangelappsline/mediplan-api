<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Lead (prospecto) de un negocio.
 */
#[OA\Schema(
    schema: 'Lead',
    title: 'Lead',
    type: 'object',
    required: ['id', 'business_id', 'name', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'business_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Carlos Ramírez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'carlos@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '5598765432'),
        new OA\Property(property: 'company', type: 'string', nullable: true, example: 'Grupo Ramírez'),
        new OA\Property(property: 'source', description: 'Origen del lead (facebook, referencia, sitio_web, etc.).', type: 'string', nullable: true, example: 'facebook'),
        new OA\Property(
            property: 'status',
            description: 'Estado del embudo. is_open indica si todavía puede trabajarse.',
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'contacted'),
                new OA\Property(property: 'label', type: 'string', example: 'Contactado'),
                new OA\Property(property: 'is_open', type: 'boolean', example: true),
            ],
        ),
        new OA\Property(property: 'estimated_value', type: 'number', format: 'float', nullable: true, example: 4500.0),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'assigned_to', nullable: true, ref: User::class),
        new OA\Property(property: 'follow_up_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'contacted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'converted_client', nullable: true, ref: Client::class),
        new OA\Property(property: 'converted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class Lead
{
}
