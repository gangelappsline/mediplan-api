<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para crear o actualizar un lead.
 */
#[OA\Schema(
    schema: 'LeadRequest',
    title: 'Lead (cuerpo de la petición)',
    description: 'Debes enviar al menos un correo electrónico o un teléfono.',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180, example: 'Carlos Ramírez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'carlos@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '5598765432'),
        new OA\Property(property: 'company', type: 'string', nullable: true, example: 'Grupo Ramírez'),
        new OA\Property(property: 'source', type: 'string', nullable: true, example: 'facebook'),
        new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'], default: 'new', example: 'new'),
        new OA\Property(property: 'estimated_value', type: 'number', format: 'float', nullable: true, example: 4500),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 2000),
        new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'follow_up_at', type: 'string', format: 'date-time', nullable: true, example: '2026-10-15T17:00:00.000000Z'),
    ],
)]
final class LeadRequest
{
}
