<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\LeadListResponse;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Supervisión global del embudo de leads de todos los negocios.
 */
class LeadController extends Controller
{
    use RespondsWithPaginatedResources;

    #[OA\Get(
        path: '/admin/leads',
        tags: ['Leads (administración)'],
        summary: 'Listar leads de la plataforma',
        description: 'Vista global de solo lectura de los leads de todos los negocios, útil para supervisión y reportes.',
        operationId: 'adminLeadsIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])),
            new OA\Parameter(name: 'business_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Leads de la plataforma.', content: new OA\JsonContent(ref: LeadListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 422, description: 'Filtros inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(LeadStatus::values())],
            'business_id' => ['nullable', 'integer', 'exists:businesses,id'],
        ]);

        $paginator = Lead::query()
            ->with(['assignedTo', 'business'])
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->when(isset($filters['business_id']), fn ($query) => $query->where('business_id', $filters['business_id']))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, LeadResource::class, 'Leads obtenidos correctamente.');
    }
}
