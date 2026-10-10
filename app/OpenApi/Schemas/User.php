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
    required: ['id', 'name', 'email', 'phone', 'email_verified_at', 'is_active', 'roles', 'created_at'],
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
            property: 'phone',
            description: 'Teléfono de contacto. Puede ser null.',
            type: 'string',
            nullable: true,
            maxLength: 30,
            example: '5512345678',
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
            property: 'is_active',
            description: 'Una cuenta desactivada no puede iniciar sesión.',
            type: 'boolean',
            example: true,
        ),
        new OA\Property(
            property: 'business',
            description: 'Negocio del que el usuario es propietario. Solo se incluye cuando se carga la relación y el usuario tiene rol negocio.',
            nullable: true,
            ref: Business::class,
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
        'phone' => null,
        'email_verified_at' => null,
        'is_active' => true,
        'roles' => [
            ['name' => 'client', 'label' => 'Cliente'],
        ],
        'business' => null,
        'created_at' => '2026-10-10T15:30:00.000000Z',
    ],
)]
final class User
{
}
