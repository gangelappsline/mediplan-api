<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Negocio registrado en la plataforma.
 */
#[OA\Schema(
    schema: 'Business',
    title: 'Negocio',
    type: 'object',
    required: ['id', 'name', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Clínica Dental Sonrisa'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Consultorio dental integral.'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'contacto@sonrisa.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '5512345678'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Reforma 123'),
        new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Ciudad de México'),
        new OA\Property(property: 'status', ref: Status::class),
        new OA\Property(property: 'owner', description: 'Usuario propietario. Solo se incluye cuando se carga la relación.', nullable: true, ref: User::class),
        new OA\Property(property: 'clients_count', description: 'Total de clientes. Solo aparece cuando se solicita el conteo.', type: 'integer', nullable: true, example: 42),
        new OA\Property(property: 'leads_count', description: 'Total de leads. Solo aparece cuando se solicita el conteo.', type: 'integer', nullable: true, example: 17),
        new OA\Property(property: 'appointments_count', description: 'Total de citas. Solo aparece cuando se solicita el conteo.', type: 'integer', nullable: true, example: 88),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class Business
{
}
