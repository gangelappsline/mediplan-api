<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Representación pública de un usuario. Nunca incluye la contraseña.
 */
#[OA\Schema(
    schema: 'User',
    title: 'Usuario',
    description: 'Recurso público de usuario retornado por los endpoints de autenticación.',
    type: 'object',
    required: ['id', 'name', 'email', 'email_verified_at', 'roles', 'created_at'],
    properties: [
        new OA\Property(
            property: 'id',
            description: 'Identificador único del usuario.',
            type: 'integer',
            format: 'int64',
            example: 1,
        ),
        new OA\Property(
            property: 'name',
            description: 'Nombre visible del usuario.',
            type: 'string',
            maxLength: 255,
            example: 'Juan Pérez',
        ),
        new OA\Property(
            property: 'email',
            description: 'Correo electrónico único utilizado para iniciar sesión.',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'juan@example.com',
        ),
        new OA\Property(
            property: 'email_verified_at',
            description: 'Fecha de verificación del correo en formato ISO 8601. Es null si no se ha verificado.',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: null,
        ),
        new OA\Property(
            property: 'roles',
            description: 'Lista de roles asignados al usuario. Puede ser un arreglo vacío.',
            type: 'array',
            items: new OA\Items(ref: Role::class),
            example: [
                ['name' => 'client', 'label' => 'Cliente'],
            ],
        ),
        new OA\Property(
            property: 'created_at',
            description: 'Fecha de creación del usuario en formato ISO 8601.',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-10-10T15:30:00.000000Z',
        ),
    ],
    example: [
        'id' => 1,
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'email_verified_at' => null,
        'roles' => [
            ['name' => 'client', 'label' => 'Cliente'],
        ],
        'created_at' => '2026-10-10T15:30:00.000000Z',
    ],
)]
final class User
{
}
