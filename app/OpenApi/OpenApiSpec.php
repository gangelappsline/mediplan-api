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
        new OA\Tag(
            name: 'Panel del negocio',
            description: 'Indicadores del negocio autenticado (rol negocio).',
        ),
        new OA\Tag(
            name: 'Clientes del negocio',
            description: 'Directorio de clientes del negocio autenticado (rol negocio).',
        ),
        new OA\Tag(
            name: 'Leads del negocio',
            description: 'Embudo de leads del negocio autenticado (rol negocio).',
        ),
        new OA\Tag(
            name: 'Agenda del negocio',
            description: 'Calendario y citas del negocio autenticado (rol negocio).',
        ),
        new OA\Tag(
            name: 'Configuración del negocio',
            description: 'Perfil y configuración operativa del negocio autenticado (rol negocio).',
        ),
        new OA\Tag(
            name: 'Panel del cliente',
            description: 'Indicadores de la cuenta del usuario cliente (rol cliente).',
        ),
        new OA\Tag(
            name: 'Citas del cliente',
            description: 'Citas asociadas a la cuenta del usuario cliente (rol cliente).',
        ),
        new OA\Tag(
            name: 'Panel de administración',
            description: 'Indicadores globales de la plataforma (rol administrador).',
        ),
        new OA\Tag(
            name: 'Usuarios (administración)',
            description: 'Alta, edición, roles, activación y baja de usuarios (rol administrador).',
        ),
        new OA\Tag(
            name: 'Negocios (administración)',
            description: 'Listado, edición, moderación y baja de negocios (rol administrador).',
        ),
        new OA\Tag(
            name: 'Leads (administración)',
            description: 'Supervisión de solo lectura de los leads de todos los negocios (rol administrador).',
        ),
        new OA\Tag(
            name: 'Roles (administración)',
            description: 'Catálogo de roles de la plataforma (rol administrador).',
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
