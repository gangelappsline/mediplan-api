<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cliente del directorio de un negocio.
 */
#[OA\Schema(
    schema: 'Client',
    title: 'Cliente',
    type: 'object',
    required: ['id', 'business_id', 'name', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'business_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'María López'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'maria@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '5512345678'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true, example: '1990-04-12'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Prefiere citas por la tarde.'),
        new OA\Property(property: 'status', ref: Status::class),
        new OA\Property(property: 'user', description: 'Cuenta de plataforma vinculada, si existe.', nullable: true, ref: User::class),
        new OA\Property(property: 'business', nullable: true, ref: Business::class),
        new OA\Property(property: 'appointments_count', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'last_appointment_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class Client
{
}
