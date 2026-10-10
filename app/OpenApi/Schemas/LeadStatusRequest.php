<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para mover un lead dentro del embudo.
 */
#[OA\Schema(
    schema: 'LeadStatusRequest',
    title: 'Estado del lead (cuerpo de la petición)',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'], example: 'contacted'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 2000, example: 'Se envió cotización por WhatsApp.'),
        new OA\Property(property: 'follow_up_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class LeadStatusRequest
{
}
