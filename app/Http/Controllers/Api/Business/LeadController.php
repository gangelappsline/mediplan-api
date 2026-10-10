<?php

namespace App\Http\Controllers\Api\Business;

use App\Enums\LeadStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBusiness;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\ConvertLeadRequest;
use App\Http\Requests\Business\StoreLeadRequest;
use App\Http\Requests\Business\UpdateLeadRequest;
use App\Http\Requests\Business\UpdateLeadStatusRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\LeadResource;
use App\Models\Business;
use App\Models\Lead;
use App\OpenApi\Schemas\ClientResponse;
use App\OpenApi\Schemas\ConvertLeadRequest as ConvertLeadRequestSchema;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\LeadListResponse;
use App\OpenApi\Schemas\LeadRequest;
use App\OpenApi\Schemas\LeadResponse;
use App\OpenApi\Schemas\LeadStatusRequest;
use App\OpenApi\Schemas\MessageResponse;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use App\Services\Leads\LeadConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Embudo de leads de un negocio.
 */
class LeadController extends Controller
{
    use ResolvesCurrentBusiness;
    use RespondsWithPaginatedResources;

    public function __construct(private readonly LeadConverter $leadConverter)
    {
    }

    #[OA\Get(
        path: '/business/leads',
        tags: ['Leads del negocio'],
        summary: 'Listar los leads del negocio',
        description: 'Listado paginado del embudo. Admite búsqueda, filtro por estado u origen y por rango de fechas de seguimiento.',
        operationId: 'businessLeadsIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])),
            new OA\Parameter(name: 'source', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'open', in: 'query', required: false, description: 'Con 1 devuelve solo los leads sin cerrar.', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'follow_up_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'follow_up_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['name', 'created_at', 'follow_up_at', 'estimated_value'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Leads del negocio.', content: new OA\JsonContent(ref: LeadListResponse::class)),
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
            'status' => ['nullable', Rule::in(LeadStatus::values())],
            'source' => ['nullable', 'string', 'max:60'],
            'open' => ['nullable', 'boolean'],
            'follow_up_from' => ['nullable', 'date'],
            'follow_up_to' => ['nullable', 'date', 'after_or_equal:follow_up_from'],
            'sort' => ['nullable', Rule::in(['name', 'created_at', 'follow_up_at', 'estimated_value'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $paginator = $business->leads()
            ->with('assignedTo')
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->when(! empty($filters['open']), fn ($query) => $query->open())
            ->source($filters['source'] ?? null)
            ->when(isset($filters['follow_up_from']), fn ($query) => $query->where('follow_up_at', '>=', $filters['follow_up_from']))
            ->when(isset($filters['follow_up_to']), fn ($query) => $query->where('follow_up_at', '<=', $filters['follow_up_to']))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, LeadResource::class, 'Leads obtenidos correctamente.');
    }

    #[OA\Get(
        path: '/business/leads/{lead}',
        tags: ['Leads del negocio'],
        summary: 'Obtener un lead',
        operationId: 'businessLeadsShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lead solicitado.', content: new OA\JsonContent(ref: LeadResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El lead no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Request $request, Lead $lead): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($lead, $business);

        $lead->load(['assignedTo', 'convertedClient']);

        return response()->json([
            'message' => 'Lead obtenido correctamente.',
            'data' => new LeadResource($lead),
        ]);
    }

    #[OA\Post(
        path: '/business/leads',
        tags: ['Leads del negocio'],
        summary: 'Crear un lead',
        description: 'Registra un prospecto. Debes enviar al menos un correo electrónico o un teléfono.',
        operationId: 'businessLeadsStore',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos del lead.',
            content: new OA\JsonContent(ref: LeadRequest::class),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Lead creado.', content: new OA\JsonContent(ref: LeadResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function store(StoreLeadRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $lead = $business->leads()->create($request->validated() + [
            'status' => $request->input('status', LeadStatus::New->value),
        ]);

        $lead->load('assignedTo');

        return response()->json([
            'message' => 'Lead creado correctamente.',
            'data' => new LeadResource($lead),
        ], 201);
    }

    #[OA\Put(
        path: '/business/leads/{lead}',
        tags: ['Leads del negocio'],
        summary: 'Actualizar un lead',
        operationId: 'businessLeadsUpdate',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos a actualizar.',
            content: new OA\JsonContent(ref: LeadRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Lead actualizado.', content: new OA\JsonContent(ref: LeadResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El lead no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($lead, $business);

        $data = $request->validated();

        $status = LeadStatus::coerce($data['status'] ?? null);

        if ($status !== null && $status !== LeadStatus::New && $lead->contacted_at === null) {
            $data['contacted_at'] = now();
        }

        $lead->update($data);
        $lead->load('assignedTo');

        return response()->json([
            'message' => 'Lead actualizado correctamente.',
            'data' => new LeadResource($lead),
        ]);
    }

    #[OA\Patch(
        path: '/business/leads/{lead}/status',
        tags: ['Leads del negocio'],
        summary: 'Mover un lead dentro del embudo',
        description: 'Cambia el estado del lead. Al pasar de new a cualquier otro estado se registra contacted_at automáticamente.',
        operationId: 'businessLeadsUpdateStatus',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Nuevo estado del lead.',
            content: new OA\JsonContent(ref: LeadStatusRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado.', content: new OA\JsonContent(ref: LeadResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El lead no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Estado inválido.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($lead, $business);

        $data = $request->validated();
        $status = LeadStatus::from($data['status']);

        if ($status !== LeadStatus::New && $lead->contacted_at === null) {
            $data['contacted_at'] = now();
        }

        $lead->update($data);
        $lead->load('assignedTo');

        return response()->json([
            'message' => 'Estado del lead actualizado correctamente.',
            'data' => new LeadResource($lead),
        ]);
    }

    #[OA\Post(
        path: '/business/leads/{lead}/convert',
        tags: ['Leads del negocio'],
        summary: 'Convertir un lead en cliente',
        description: 'Crea un cliente con los datos del lead (o con los enviados en el cuerpo) y marca el lead como ganado. Responde 422 si el lead ya fue convertido.',
        operationId: 'businessLeadsConvert',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            description: 'Datos opcionales que sustituyen a los del lead.',
            content: new OA\JsonContent(ref: ConvertLeadRequestSchema::class),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente creado desde el lead.', content: new OA\JsonContent(ref: ClientResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El lead no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'El lead ya fue convertido o los datos son inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function convert(ConvertLeadRequest $request, Lead $lead): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($lead, $business);

        $client = $this->leadConverter->convert($lead, $request->validated());
        $client->load('lead');

        return response()->json([
            'message' => 'Lead convertido en cliente correctamente.',
            'data' => new ClientResource($client),
        ], 201);
    }

    #[OA\Delete(
        path: '/business/leads/{lead}',
        tags: ['Leads del negocio'],
        summary: 'Eliminar un lead',
        operationId: 'businessLeadsDestroy',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lead', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lead eliminado.', content: new OA\JsonContent(ref: MessageResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El lead no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function destroy(Request $request, Lead $lead): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($lead, $business);

        $lead->delete();

        return response()->json([
            'message' => 'Lead eliminado correctamente.',
        ]);
    }
}
