<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BusinessStatus;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessRequest;
use App\Http\Requests\Admin\UpdateBusinessStatusRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\OpenApi\Schemas\BusinessListResponse;
use App\OpenApi\Schemas\BusinessProfileRequest;
use App\OpenApi\Schemas\BusinessResponse;
use App\OpenApi\Schemas\BusinessStatusRequest;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\MessageResponse;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Administración de los negocios de la plataforma.
 */
class BusinessController extends Controller
{
    use RespondsWithPaginatedResources;

    #[OA\Get(
        path: '/admin/businesses',
        tags: ['Negocios (administración)'],
        summary: 'Listar negocios',
        description: 'Listado paginado de todos los negocios con su propietario y el conteo de clientes, leads y citas.',
        operationId: 'adminBusinessesIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Nombre, correo, teléfono o ciudad.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'active', 'suspended'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Negocios de la plataforma.', content: new OA\JsonContent(ref: BusinessListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 422, description: 'Filtros inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(BusinessStatus::values())],
        ]);

        $paginator = Business::query()
            ->with('user')
            ->withCount(['clients', 'leads', 'appointments'])
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, BusinessResource::class, 'Negocios obtenidos correctamente.');
    }

    #[OA\Get(
        path: '/admin/businesses/{business}',
        tags: ['Negocios (administración)'],
        summary: 'Obtener un negocio',
        operationId: 'adminBusinessesShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Negocio solicitado.', content: new OA\JsonContent(ref: BusinessResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El negocio no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Business $business): JsonResponse
    {
        $business->load('user')->loadCount(['clients', 'leads', 'appointments']);

        return response()->json([
            'message' => 'Negocio obtenido correctamente.',
            'data' => new BusinessResource($business),
        ]);
    }

    #[OA\Put(
        path: '/admin/businesses/{business}',
        tags: ['Negocios (administración)'],
        summary: 'Actualizar un negocio',
        operationId: 'adminBusinessesUpdate',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos a actualizar.',
            content: new OA\JsonContent(ref: BusinessProfileRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Negocio actualizado.', content: new OA\JsonContent(ref: BusinessResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El negocio no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateBusinessRequest $request, Business $business): JsonResponse
    {
        $business->update($request->validated());
        $business->load('user')->loadCount(['clients', 'leads', 'appointments']);

        return response()->json([
            'message' => 'Negocio actualizado correctamente.',
            'data' => new BusinessResource($business),
        ]);
    }

    #[OA\Patch(
        path: '/admin/businesses/{business}/status',
        tags: ['Negocios (administración)'],
        summary: 'Cambiar el estado de un negocio',
        description: 'Aprueba un negocio pendiente (active), suspéndelo (suspended) o regrésalo a revisión (pending).',
        operationId: 'adminBusinessesUpdateStatus',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Nuevo estado.',
            content: new OA\JsonContent(ref: BusinessStatusRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado.', content: new OA\JsonContent(ref: BusinessResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El negocio no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Estado inválido.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateStatus(UpdateBusinessStatusRequest $request, Business $business): JsonResponse
    {
        $business->update(['status' => $request->validated()['status']]);
        $business->load('user');

        return response()->json([
            'message' => 'Estado del negocio actualizado correctamente.',
            'data' => new BusinessResource($business),
        ]);
    }

    #[OA\Delete(
        path: '/admin/businesses/{business}',
        tags: ['Negocios (administración)'],
        summary: 'Eliminar un negocio',
        description: 'Elimina el negocio junto con sus clientes, leads, citas y configuración. La cuenta del propietario se conserva.',
        operationId: 'adminBusinessesDestroy',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Negocio eliminado.', content: new OA\JsonContent(ref: MessageResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El negocio no existe.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function destroy(Business $business): JsonResponse
    {
        $business->delete();

        return response()->json([
            'message' => 'Negocio eliminado correctamente.',
        ]);
    }
}
