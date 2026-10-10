<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Datos que se devuelven después de registrar o autenticar a un usuario.
 */
#[OA\Schema(
    schema: 'AuthPayload',
    title: 'Datos de autenticación',
    description: 'El token es un access token de Laravel Passport. Trátalo como un secreto, guárdalo de forma segura y no lo expongas en logs.',
    type: 'object',
    required: ['user', 'token', 'token_type'],
    properties: [
        new OA\Property(
            property: 'user',
            description: 'Usuario autenticado.',
            ref: User::class,
        ),
        new OA\Property(
            property: 'token',
            description: 'Access token personal emitido por Passport. Su expiración configurada es de hasta 6 meses.',
            type: 'string',
            format: 'password',
            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...',
        ),
        new OA\Property(
            property: 'token_type',
            description: 'Prefijo que debe acompañar al token en la cabecera Authorization.',
            type: 'string',
            enum: ['Bearer'],
            example: 'Bearer',
        ),
    ],
)]
final class AuthPayload
{
}
