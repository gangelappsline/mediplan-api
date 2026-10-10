<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Concerns\ResolvesCurrentBusiness;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\OpenApi\Schemas\BusinessDashboardResponse;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\Services\Dashboards\BusinessDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    use ResolvesCurrentBusiness;

    public function __construct(private readonly BusinessDashboardService $dashboard)
    {
    }

    #[OA\Get(
        path: '/business/dashboard',
        tags: ['Panel del negocio'],
        summary: 'Panel de control del negocio',
        description: 'Devuelve los indicadores del negocio autenticado: clientes, embudo de leads, actividad de la agenda, próximas citas y registros recientes.',
        operationId: 'businessDashboard',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Indicadores del negocio.', content: new OA\JsonContent(ref: BusinessDashboardResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        return response()->json([
            'message' => 'Panel del negocio generado correctamente.',
            'data' => $this->dashboard->for($business),
        ]);
    }
}
