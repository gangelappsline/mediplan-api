<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para actualizar los datos públicos del negocio.
 */
#[OA\Schema(
    schema: 'BusinessProfileRequest',
    title: 'Perfil del negocio (cuerpo de la petición)',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180, example: 'Clínica Dental Sonrisa'),
        new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'contacto@sonrisa.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 30, example: '5512345678'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Reforma 123'),
        new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Ciudad de México'),
    ],
)]
final class BusinessProfileRequest
{
}
