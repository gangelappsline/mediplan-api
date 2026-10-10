<?php

namespace App\Http\Controllers\Api\Client;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CancelAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\OpenApi\Schemas\AppointmentListResponse;
use App\OpenApi\Schemas\AppointmentResponse;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Citas del usuario cliente.
 */
class AppointmentController extends Controller
{
    use RespondsWithPaginatedResources;

    #[OA\Get(
        path: '/client/appointments',
        tags: ['Citas del cliente'],
        summary: 'Listar mis citas',
        description: 'Citas asociadas a la cuenta autenticada. Usa scope=upcoming para las próximas o scope=past para el histórico.',
        operationId: 'clientAppointmentsIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'scope', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'upcoming', 'past'], default: 'all')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Citas del usuario.', content: new OA\JsonContent(ref: AppointmentListResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol cliente.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 422, description: 'Filtros inválidos.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(['all', 'upcoming', 'past'])],
            'status' => ['nullable', Rule::in(AppointmentStatus::values())],
        ]);

        $scope = $filters['scope'] ?? 'all';

        $paginator = $request->user()->appointments()
            ->with(['business', 'client'])
            ->when($scope === 'upcoming', fn ($query) => $query->upcoming()->booked())
            ->when($scope === 'past', fn ($query) => $query->past())
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->orderBy('starts_at', $scope === 'past' ? 'desc' : 'asc')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, AppointmentResource::class, 'Citas obtenidas correctamente.');
    }

    #[OA\Get(
        path: '/client/appointments/{appointment}',
        tags: ['Citas del cliente'],
        summary: 'Obtener una de mis citas',
        operationId: 'clientAppointmentsShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cita solicitada.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol cliente.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o no pertenece al usuario.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->assertOwnedBy($request, $appointment);

        $appointment->load(['business', 'client']);

        return response()->json([
            'message' => 'Cita obtenida correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    #[OA\Patch(
        path: '/client/appointments/{appointment}/cancel',
        tags: ['Citas del cliente'],
        summary: 'Cancelar una de mis citas',
        description: 'Solo pueden cancelarse citas que siguen agendadas (programada o confirmada) y cuya fecha no haya pasado.',
        operationId: 'clientAppointmentsCancel',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            description: 'Motivo opcional de la cancelación.',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'cancel_reason', type: 'string', nullable: true, maxLength: 255, example: 'Se me complicó la semana.'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cita cancelada.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol cliente.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o no pertenece al usuario.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'La cita ya no puede cancelarse.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->assertOwnedBy($request, $appointment);

        if (! $appointment->isBooked()) {
            throw ValidationException::withMessages([
                'status' => 'Esta cita ya no puede cancelarse.',
            ]);
        }

        if ($appointment->starts_at?->isPast() ?? false) {
            throw ValidationException::withMessages([
                'starts_at' => 'No puedes cancelar una cita cuya fecha ya pasó.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $request->input('cancel_reason', 'Cancelada por el cliente.'),
        ]);

        $appointment->load(['business', 'client']);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    private function assertOwnedBy(Request $request, Appointment $appointment): void
    {
        if ((int) $appointment->user_id !== (int) $request->user()->getKey()) {
            abort(404, 'El recurso solicitado no existe.');
        }
    }
}
