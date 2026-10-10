<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\RoleListResponse;
use App\OpenApi\Schemas\UnauthenticatedError;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Catálogo de roles disponibles en la plataforma.
 */
class RoleController extends Controller
{
    #[OA\Get(
        path: '/admin/roles',
        tags: ['Roles (administración)'],
        summary: 'Listar roles',
        description: 'Roles registrados con su etiqueta en español y la cantidad de usuarios asignados.',
        operationId: 'adminRolesIndex',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Roles de la plataforma.', content: new OA\JsonContent(ref: RoleListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
        ],
    )]
    public function index(): JsonResponse
    {
        $roles = Role::query()->withCount('users')->orderBy('id')->get();

        return response()->json([
            'message' => 'Roles obtenidos correctamente.',
            'data' => $roles->map(fn (Role $role) => [
                'id' => $role->getKey(),
                'name' => $role->name,
                'label' => $role->label,
                'description' => $role->description,
                'users_count' => (int) $role->users_count,
            ])->values(),
        ]);
    }
}
