<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para crear un usuario desde el panel de administración.
 */
#[OA\Schema(
    schema: 'AdminUserRequest',
    title: 'Usuario de administración (cuerpo de la petición)',
    type: 'object',
    required: ['name', 'email', 'password', 'password_confirmation', 'role'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ana Torres'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@mediplan.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 30),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
        new OA\Property(
            property: 'role',
            description: 'Acepta el valor en inglés o su slug en español.',
            type: 'string',
            enum: ['client', 'business', 'admin', 'cliente', 'negocio', 'administrador'],
            example: 'business',
        ),
        new OA\Property(property: 'business_name', description: 'Obligatorio cuando el rol es negocio.', type: 'string', nullable: true, example: 'Clínica Torres'),
        new OA\Property(property: 'is_active', type: 'boolean', default: true, example: true),
    ],
)]
final class AdminUserRequest
{
}
