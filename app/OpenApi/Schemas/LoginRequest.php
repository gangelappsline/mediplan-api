<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo requerido por POST /login.
 */
#[OA\Schema(
    schema: 'LoginRequest',
    title: 'Solicitud de inicio de sesión',
    description: 'Credenciales de un usuario existente.',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(
            property: 'email',
            description: 'Correo utilizado durante el registro.',
            type: 'string',
            format: 'email',
            example: 'juan@example.com',
        ),
        new OA\Property(
            property: 'password',
            description: 'Contraseña asociada al correo.',
            type: 'string',
            format: 'password',
            minLength: 1,
            writeOnly: true,
            example: 'password123',
        ),
    ],
    example: [
        'email' => 'juan@example.com',
        'password' => 'password123',
    ],
)]
final class LoginRequest
{
}
