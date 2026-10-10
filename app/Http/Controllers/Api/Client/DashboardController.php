<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\OpenApi\Schemas\ClientDashboardResponse;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\Services\Dashboards\ClientDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(private readonly ClientDashboardService $dashboard)
    {
    }

    #[OA\Get(
        path: '/client/dashboard',
        tags: ['Panel del cliente'],
        summary: 'Panel de control del cliente',
        description: 'Resumen de la cuenta del usuario cliente: próxima cita, citas por venir, histórico y negocios donde está dado de alta.',
        operationId: 'clientDashboard',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Indicadores del cliente.', content: new OA\JsonContent(ref: ClientDashboardResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol cliente.', content: new OA\JsonContent(ref: ForbiddenError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Panel del cliente generado correctamente.',
            'data' => $this->dashboard->for($request->user()),
        ]);
    }
}
