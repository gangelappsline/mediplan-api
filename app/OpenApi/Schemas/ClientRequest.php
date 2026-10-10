<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para crear o actualizar un cliente del negocio.
 */
#[OA\Schema(
    schema: 'ClientRequest',
    title: 'Cliente (cuerpo de la petición)',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180, example: 'María López'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'maria@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '5512345678'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true, example: '1990-04-12'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 2000),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], default: 'active', example: 'active'),
        new OA\Property(property: 'user_id', description: 'Cuenta de plataforma a vincular (opcional).', type: 'integer', nullable: true),
    ],
)]
final class ClientRequest
{
}
