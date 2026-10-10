<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Error 422 producido por un FormRequest de Laravel.
 */
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Error de validación',
    description: 'La propiedad errors contiene una lista de mensajes por nombre de campo. Un frontend debe mostrar el primer mensaje de cada arreglo.',
    type: 'object',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(
            property: 'message',
            description: 'Resumen de la validación. Puede contener el primer mensaje y el texto de cantidad de errores de Laravel; para asociar mensajes a campos usa errors.',
            type: 'string',
            example: 'Este correo electrónico ya está registrado.',
        ),
        new OA\Property(
            property: 'errors',
            description: 'Mapa campo => lista de mensajes. En registro las claves posibles son name, email, password, password_confirmation y role; en login son email y password.',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['El nombre es obligatorio.'],
                ),
                new OA\Property(
                    property: 'email',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['El correo electrónico es obligatorio.'],
                ),
                new OA\Property(
                    property: 'password',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['La contraseña debe tener al menos 8 caracteres.'],
                ),
                new OA\Property(
                    property: 'password_confirmation',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['La confirmación de la contraseña no coincide.'],
                ),
                new OA\Property(
                    property: 'role',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['El rol seleccionado no es válido. Valores permitidos: cliente, negocio.'],
                ),
            ],
        ),
    ],
    example: [
        'message' => 'Este correo electrónico ya está registrado.',
        'errors' => [
            'email' => ['Este correo electrónico ya está registrado.'],
        ],
    ],
)]
final class ValidationError
{
}
