<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo para activar o desactivar una cuenta.
 */
#[OA\Schema(
    schema: 'UserStatusRequest',
    title: 'Estado de la cuenta (cuerpo de la petición)',
    type: 'object',
    required: ['is_active'],
    properties: [
        new OA\Property(property: 'is_active', type: 'boolean', example: false),
    ],
)]
final class UserStatusRequest
{
}
