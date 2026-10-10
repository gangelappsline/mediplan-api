<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo requerido por POST /register.
 */
#[OA\Schema(
    schema: 'RegisterRequest',
    title: 'Solicitud de registro',
    description: 'Todos los campos son obligatorios. El auto-registro solo permite los roles cliente y negocio; administrador no puede enviarse desde este endpoint.',
    type: 'object',
    required: ['name', 'email', 'password', 'password_confirmation', 'role'],
    properties: [
        new OA\Property(
            property: 'name',
            description: 'Nombre visible del usuario.',
            type: 'string',
            minLength: 1,
            maxLength: 255,
            example: 'Juan Pérez',
        ),
        new OA\Property(
            property: 'email',
            description: 'Correo válido y no registrado previamente. La comparación de unicidad se realiza contra users.email.',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'juan@example.com',
        ),
        new OA\Property(
            property: 'password',
            description: 'Contraseña en texto plano únicamente dentro de la solicitud HTTPS. Debe tener al menos 8 caracteres.',
            type: 'string',
            format: 'password',
            minLength: 8,
            writeOnly: true,
            example: 'password123',
        ),
        new OA\Property(
            property: 'password_confirmation',
            description: 'Debe coincidir exactamente con password.',
            type: 'string',
            format: 'password',
            minLength: 8,
            writeOnly: true,
            example: 'password123',
        ),
        new OA\Property(
            property: 'role',
            description: 'Rol solicitado. administrador y admin son rechazados por seguridad.',
            type: 'string',
            enum: ['cliente', 'negocio'],
            example: 'cliente',
        ),
    ],
    example: [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'cliente',
    ],
)]
final class RegisterRequest
{
}
