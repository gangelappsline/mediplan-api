<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo opcional para convertir un lead en cliente.
 */
#[OA\Schema(
    schema: 'ConvertLeadRequest',
    title: 'Conversión de lead (cuerpo de la petición)',
    description: 'Todos los campos son opcionales: si se omiten se usan los datos del lead.',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', nullable: true, maxLength: 180),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 30),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 2000),
    ],
)]
final class ConvertLeadRequest
{
}
