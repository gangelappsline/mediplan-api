<?php

namespace App\Http\Controllers\Api\Business;

use App\Enums\ClientStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBusiness;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\StoreClientRequest;
use App\Http\Requests\Business\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Business;
use App\Models\Client;
use App\OpenApi\Schemas\ClientListResponse;
use App\OpenApi\Schemas\ClientRequest;
use App\OpenApi\Schemas\ClientResponse;
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
 * Directorio de clientes de un negocio.
 */
class ClientController extends Controller
{
    use ResolvesCurrentBusiness;
    use RespondsWithPaginatedResources;

    /**
     * Columnas por las que se puede ordenar el listado.
     *
     * @var list<string>
     */
    private const SORTABLE = ['name', 'created_at', 'last_appointment_at'];

    #[OA\Get(
        path: '/business/clients',
        tags: ['Clientes del negocio'],
        summary: 'Listar los clientes del negocio',
        description: 'Listado paginado del directorio de clientes. Admite búsqueda por nombre, correo o teléfono, filtro por estado y ordenamiento.',
        operationId: 'businessClientsIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Nombre, correo o teléfono.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Filtra por estado.', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Columna de orden.', schema: new OA\Schema(type: 'string', enum: self::SORTABLE, default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, description: 'Sentido del orden.', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Elementos por página (máximo 100).', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clientes del negocio.', content: new OA\JsonContent(ref: ClientListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Filtros inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(ClientStatus::values())],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $paginator = $business->clients()
            ->withCount('appointments')
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, ClientResource::class, 'Clientes obtenidos correctamente.');
    }

    #[OA\Get(
        path: '/business/clients/{client}',
        tags: ['Clientes del negocio'],
        summary: 'Obtener un cliente',
        operationId: 'businessClientsShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, description: 'Identificador del cliente.', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente solicitado.', content: new OA\JsonContent(ref: ClientResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El cliente no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Request $request, Client $client): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($client, $business);

        $client->load(['user', 'business'])->loadCount('appointments');

        return response()->json([
            'message' => 'Cliente obtenido correctamente.',
            'data' => new ClientResource($client),
        ]);
    }

    #[OA\Post(
        path: '/business/clients',
        tags: ['Clientes del negocio'],
        summary: 'Crear un cliente',
        description: 'Da de alta un cliente en el directorio del negocio autenticado. El correo debe ser único dentro del mismo negocio.',
        operationId: 'businessClientsStore',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos del cliente.',
            content: new OA\JsonContent(ref: ClientRequest::class),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente creado.', content: new OA\JsonContent(ref: ClientResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function store(StoreClientRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $client = $business->clients()->create($request->validated() + [
            'status' => $request->input('status', ClientStatus::Active->value),
        ]);

        $client->load('user');

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => new ClientResource($client),
        ], 201);
    }

    #[OA\Put(
        path: '/business/clients/{client}',
        tags: ['Clientes del negocio'],
        summary: 'Actualizar un cliente',
        description: 'Actualización parcial de los datos del cliente.',
        operationId: 'businessClientsUpdate',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos a actualizar.',
            content: new OA\JsonContent(ref: ClientRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cliente actualizado.', content: new OA\JsonContent(ref: ClientResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El cliente no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($client, $business);

        $client->update($request->validated());
        $client->load('user');

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data' => new ClientResource($client),
        ]);
    }

    #[OA\Delete(
        path: '/business/clients/{client}',
        tags: ['Clientes del negocio'],
        summary: 'Eliminar un cliente',
        description: 'Elimina el cliente y todas sus citas asociadas.',
        operationId: 'businessClientsDestroy',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente eliminado.', content: new OA\JsonContent(ref: MessageResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El cliente no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function destroy(Request $request, Client $client): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($client, $business);

        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }
}
