<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Concerns\ResolvesCurrentBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessProfileRequest;
use App\Http\Requests\Business\UpdateBusinessSettingsRequest;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\BusinessSettingResource;
use App\Models\Business;
use App\OpenApi\Schemas\BusinessProfileRequest;
use App\OpenApi\Schemas\BusinessResponse;
use App\OpenApi\Schemas\BusinessSettingsRequest;
use App\OpenApi\Schemas\BusinessSettingsResponse;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Perfil público y configuración operativa del negocio autenticado.
 */
class ProfileController extends Controller
{
    use ResolvesCurrentBusiness;

    #[OA\Get(
        path: '/business/profile',
        tags: ['Configuración del negocio'],
        summary: 'Obtener el perfil del negocio',
        description: 'Datos del negocio asociado al usuario autenticado.',
        operationId: 'businessProfileShow',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Perfil del negocio.', content: new OA\JsonContent(ref: BusinessResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $business->load('user');

        return response()->json([
            'message' => 'Negocio obtenido correctamente.',
            'data' => new BusinessResource($business),
        ]);
    }

    #[OA\Put(
        path: '/business/profile',
        tags: ['Configuración del negocio'],
        summary: 'Actualizar el perfil del negocio',
        description: 'Actualización parcial: envía solo los campos que quieras cambiar.',
        operationId: 'businessProfileUpdate',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos del negocio.',
            content: new OA\JsonContent(ref: BusinessProfileRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Perfil actualizado.', content: new OA\JsonContent(ref: BusinessResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateBusinessProfileRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $business->update($request->validated());
        $business->load('user');

        return response()->json([
            'message' => 'Negocio actualizado correctamente.',
            'data' => new BusinessResource($business),
        ]);
    }

    #[OA\Get(
        path: '/business/settings',
        tags: ['Configuración del negocio'],
        summary: 'Obtener la configuración del negocio',
        description: 'Zona horaria, duración de citas, horarios de atención y preferencias de reserva. Si el negocio todavía no la personaliza, se crea con los valores por defecto.',
        operationId: 'businessSettingsShow',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuración del negocio.', content: new OA\JsonContent(ref: BusinessSettingsResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function settings(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        return response()->json([
            'message' => 'Configuración obtenida correctamente.',
            'data' => new BusinessSettingResource($business->settings()),
        ]);
    }

    #[OA\Put(
        path: '/business/settings',
        tags: ['Configuración del negocio'],
        summary: 'Actualizar la configuración del negocio',
        description: 'Actualización parcial. En working_hours solo se permiten las claves monday, tuesday, wednesday, thursday, friday, saturday y sunday.',
        operationId: 'businessSettingsUpdate',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Configuración operativa.',
            content: new OA\JsonContent(ref: BusinessSettingsRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Configuración actualizada.', content: new OA\JsonContent(ref: BusinessSettingsResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateSettings(UpdateBusinessSettingsRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $settings = $business->settings();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Configuración actualizada correctamente.',
            'data' => new BusinessSettingResource($settings->refresh()),
        ]);
    }
}
