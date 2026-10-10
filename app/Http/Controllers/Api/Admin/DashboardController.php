<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\OpenApi\Schemas\AdminDashboardResponse;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\Services\Dashboards\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard)
    {
    }

    #[OA\Get(
        path: '/admin/dashboard',
        tags: ['Panel de administración'],
        summary: 'Panel de control de la plataforma',
        description: 'Indicadores globales: usuarios por rol, negocios por estado, clientes, leads, citas y actividad reciente.',
        operationId: 'adminDashboard',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Indicadores de la plataforma.', content: new OA\JsonContent(ref: AdminDashboardResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol administrador.', content: new OA\JsonContent(ref: ForbiddenError::class)),
        ],
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Panel de administración generado correctamente.',
            'data' => $this->dashboard->overview(),
        ]);
    }
}
