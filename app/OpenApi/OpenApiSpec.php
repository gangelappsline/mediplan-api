<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Metadatos generales de la especificación OpenAPI de MediPlan.
 *
 * Los endpoints se documentan junto a AuthController y los contratos de
 * entrada/salida se encuentran en App\OpenApi\Schemas.
 */
#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        version: '1.0.0',
        title: 'MediPlan API',
        description: <<<'DESCRIPTION'
API de autenticación de MediPlan.

Todos los endpoints están bajo el prefijo /api. Las respuestas y mensajes están en español. Para consumir un endpoint protegido, primero obtén el token de POST /register o POST /login y envíalo en cada solicitud posterior con la cabecera Authorization: Bearer <token>.
DESCRIPTION,
        license: new OA\License(name: 'MIT'),
    ),
    servers: [
        new OA\Server(
            url: '{scheme}://{host}{basePath}',
            description: 'Servidor de MediPlan',
            variables: [
                'scheme' => new OA\ServerVariable(
                    serverVariable: 'scheme',
                    description: 'Protocolo del servidor',
                    default: 'http',
                    enum: ['http', 'https'],
                ),
                'host' => new OA\ServerVariable(
                    serverVariable: 'host',
                    description: 'Dominio y puerto de la API',
                    default: 'localhost:8000',
                ),
                'basePath' => new OA\ServerVariable(
                    serverVariable: 'basePath',
                    description: 'Prefijo de las rutas HTTP',
                    default: '/api',
                ),
            ],
        ),
    ],
    tags: [
        new OA\Tag(
            name: 'Autenticación',
            description: 'Registro, inicio de sesión, cierre de sesión y consulta del usuario actual.',
        ),
    ],
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'OAuth2 access token',
    description: 'Token personal de Laravel Passport. Envíalo como Authorization: Bearer <token>.',
)]
final class OpenApiSpec
{
}
