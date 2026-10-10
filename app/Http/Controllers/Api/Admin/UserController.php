<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\OpenApi\Schemas\AdminUserRequest;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\MessageResponse;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\UserListResponse;
use App\OpenApi\Schemas\UserResponse;
use App\OpenApi\Schemas\UserRolesRequest;
use App\OpenApi\Schemas\UserStatusRequest;
use App\OpenApi\Schemas\ValidationError;
use App\Services\Businesses\BusinessProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * Administración de usuarios de la plataforma.
 */
class UserController extends Controller
{
    use RespondsWithPaginatedResources;

    public function __construct(private readonly BusinessProvisioner $provisioner)
    {
    }

    #[OA\Get(
        path: '/admin/users',
        tags: ['Usuarios (administración)'],
        summary: 'Listar usuarios',
        description: 'Listado paginado de todos los usuarios de la plataforma con sus roles y, cuando aplica, su negocio.',
        operationId: 'adminUsersIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Nombre o correo.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', required: false, description: 'Filtra por rol (inglés o español).', schema: new OA\Schema(type: 'string', enum: ['client', 'business', 'admin', 'cliente', 'negocio', 'administrador'])),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuarios de la plataforma.', content: new OA\JsonContent(ref: UserListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 422, description: 'Filtros inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(RoleName::acceptableInputs())],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $role = RoleName::coerce($filters['role'] ?? null);

        $paginator = User::query()
            ->with(['roles', 'business'])
            ->when(($filters['search'] ?? null) !== null && $filters['search'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['search'].'%';

                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->when($role !== null, fn ($query) => $query->whereHas('roles', fn ($inner) => $inner->where('name', $role->value)))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, UserResource::class, 'Usuarios obtenidos correctamente.');
    }

    #[OA\Get(
        path: '/admin/users/{user}',
        tags: ['Usuarios (administración)'],
        summary: 'Obtener un usuario',
        operationId: 'adminUsersShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario solicitado.', content: new OA\JsonContent(ref: UserResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'business']);

        return response()->json([
            'message' => 'Usuario obtenido correctamente.',
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/admin/users',
        tags: ['Usuarios (administración)'],
        summary: 'Crear un usuario',
        description: 'Alta manual de usuarios, incluido el rol administrador. Si el rol es negocio se crea también su negocio con los valores de configuración por defecto.',
        operationId: 'adminUsersStore',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos del usuario.',
            content: new OA\JsonContent(ref: AdminUserRequest::class),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado.', content: new OA\JsonContent(ref: UserResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = RoleName::coerce($data['role']);

        $user = DB::transaction(function () use ($data, $role): User {
            /** @var User $user */
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($role !== null) {
                $user->assignRole($role);
            }

            return $user;
        });

        if ($role === RoleName::Business) {
            $this->provisioner->provisionFor($user, $data['business_name'] ?? null);
        }

        $user->load(['roles', 'business']);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => new UserResource($user),
        ], 201);
    }

    #[OA\Put(
        path: '/admin/users/{user}',
        tags: ['Usuarios (administración)'],
        summary: 'Actualizar un usuario',
        description: 'Actualización parcial. La contraseña y el rol son opcionales; si se envía el rol, se agrega a los existentes (usa PUT /admin/users/{user}/roles para reemplazarlos).',
        operationId: 'adminUsersUpdate',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos a actualizar.',
            content: new OA\JsonContent(ref: AdminUserRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado.', content: new OA\JsonContent(ref: UserResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['role'])) {
            $role = RoleName::coerce($data['role']);
            unset($data['role']);

            if ($role !== null) {
                $user->assignRole($role);
            }
        }

        $user->update(array_filter(
            $data,
            fn ($value, $key) => $value !== null || in_array($key, ['is_active', 'phone'], true),
            ARRAY_FILTER_USE_BOTH,
        ));

        $user->load(['roles', 'business']);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Patch(
        path: '/admin/users/{user}/status',
        tags: ['Usuarios (administración)'],
        summary: 'Activar o desactivar una cuenta',
        description: 'Una cuenta desactivada no puede iniciar sesión. No puedes desactivar tu propia cuenta.',
        operationId: 'adminUsersUpdateStatus',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Nuevo estado de la cuenta.',
            content: new OA\JsonContent(ref: UserStatusRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado.', content: new OA\JsonContent(ref: UserResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'No puedes desactivar tu propia cuenta.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        if ($user->getKey() === $request->user()->getKey() && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        $user->update(['is_active' => $request->boolean('is_active')]);
        $user->load(['roles', 'business']);

        return response()->json([
            'message' => $user->is_active
                ? 'Cuenta activada correctamente.'
                : 'Cuenta desactivada correctamente.',
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Put(
        path: '/admin/users/{user}/roles',
        tags: ['Usuarios (administración)'],
        summary: 'Reemplazar los roles de un usuario',
        operationId: 'adminUsersUpdateRoles',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Roles definitivos del usuario.',
            content: new OA\JsonContent(ref: UserRolesRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Roles actualizados.', content: new OA\JsonContent(ref: UserResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Roles inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateRoles(UpdateUserRolesRequest $request, User $user): JsonResponse
    {
        $roles = $request->validated()['roles'];

        $resolved = array_values(array_filter(array_map(
            fn (string $role) => RoleName::coerce($role),
            $roles,
        )));

        if ($user->getKey() === $request->user()->getKey()
            && ! in_array(RoleName::Admin, $resolved, true)) {
            throw ValidationException::withMessages([
                'roles' => 'No puedes quitarte a ti mismo el rol de administrador.',
            ]);
        }

        $user->syncRoles(...$resolved);
        $user->load(['roles', 'business']);

        return response()->json([
            'message' => 'Roles actualizados correctamente.',
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Delete(
        path: '/admin/users/{user}',
        tags: ['Usuarios (administración)'],
        summary: 'Eliminar un usuario',
        description: 'Elimina la cuenta, sus tokens y, si era dueño de un negocio, también el negocio con sus clientes, leads y citas. No puedes eliminar tu propia cuenta.',
        operationId: 'adminUsersDestroy',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario eliminado.', content: new OA\JsonContent(ref: MessageResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'No puedes eliminar tu propia cuenta.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->getKey() === $request->user()->getKey()) {
            throw ValidationException::withMessages([
                'user' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
