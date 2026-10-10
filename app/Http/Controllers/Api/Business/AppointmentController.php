<?php

namespace App\Http\Controllers\Api\Business;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBusiness;
use App\Http\Controllers\Concerns\RespondsWithPaginatedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\StoreAppointmentRequest;
use App\Http\Requests\Business\UpdateAppointmentRequest;
use App\Http\Requests\Business\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Business;
use App\OpenApi\Schemas\AgendaResponse;
use App\OpenApi\Schemas\AppointmentListResponse;
use App\OpenApi\Schemas\AppointmentRequest;
use App\OpenApi\Schemas\AppointmentResponse;
use App\OpenApi\Schemas\AppointmentStatusRequest;
use App\OpenApi\Schemas\ForbiddenError;
use App\OpenApi\Schemas\MessageResponse;
use App\OpenApi\Schemas\NotFoundError;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Agenda de citas de un negocio.
 */
class AppointmentController extends Controller
{
    use ResolvesCurrentBusiness;
    use RespondsWithPaginatedResources;

    #[OA\Get(
        path: '/business/appointments',
        tags: ['Agenda del negocio'],
        summary: 'Listar las citas del negocio',
        description: 'Listado paginado de citas. Filtra por rango de fechas, estado o cliente.',
        operationId: 'businessAppointmentsIndex',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, description: 'Fecha inicial (inclusive).', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, description: 'Fecha final (inclusive).', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])),
            new OA\Parameter(name: 'client_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'upcoming', in: 'query', required: false, description: 'Con 1 devuelve solo citas futuras aún agendadas.', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Citas del negocio.', content: new OA\JsonContent(ref: AppointmentListResponse::class)),
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
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(AppointmentStatus::values())],
            'client_id' => ['nullable', 'integer'],
            'upcoming' => ['nullable', 'boolean'],
        ]);

        $paginator = $business->appointments()
            ->with('client')
            ->betweenDates(
                isset($filters['from']) ? Carbon::parse($filters['from']) : null,
                isset($filters['to']) ? Carbon::parse($filters['to']) : null,
            )
            ->when(isset($filters['status']), fn ($query) => $query->status($filters['status']))
            ->when(isset($filters['client_id']), fn ($query) => $query->where('client_id', $filters['client_id']))
            ->when(! empty($filters['upcoming']), fn ($query) => $query->upcoming()->booked())
            ->orderBy('starts_at')
            ->paginate($this->perPage($request));

        return $this->paginatedJson($paginator, AppointmentResource::class, 'Citas obtenidas correctamente.');
    }

    #[OA\Get(
        path: '/business/appointments/agenda',
        tags: ['Agenda del negocio'],
        summary: 'Agenda agrupada por día',
        description: 'Devuelve las citas agrupadas por fecha, listas para pintar un calendario mensual o semanal. Sin parámetros devuelve el mes en curso.',
        operationId: 'businessAppointmentsAgenda',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Agenda agrupada por día.', content: new OA\JsonContent(ref: AgendaResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Rango de fechas inválido.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function agenda(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfMonth();

        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : $from->copy()->endOfMonth();

        $appointments = $business->appointments()
            ->with('client')
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get();

        $days = [];

        foreach ($appointments as $appointment) {
            $date = $appointment->starts_at?->toDateString();

            if ($date === null) {
                continue;
            }

            if (! isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'label' => $appointment->starts_at->translatedFormat('l j \d\e F'),
                    'total' => 0,
                    'appointments' => [],
                ];
            }

            $days[$date]['appointments'][] = (new AppointmentResource($appointment))->resolve();
            $days[$date]['total']++;
        }

        return response()->json([
            'message' => 'Agenda obtenida correctamente.',
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'total' => $appointments->count(),
                'days' => array_values($days),
            ],
        ]);
    }

    #[OA\Get(
        path: '/business/appointments/{appointment}',
        tags: ['Agenda del negocio'],
        summary: 'Obtener una cita',
        operationId: 'businessAppointmentsShow',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cita solicitada.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($appointment, $business);

        $appointment->load(['client', 'business']);

        return response()->json([
            'message' => 'Cita obtenida correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    #[OA\Post(
        path: '/business/appointments',
        tags: ['Agenda del negocio'],
        summary: 'Agendar una cita',
        description: 'Crea una cita para un cliente del directorio. Responde 422 si el horario se traslapa con otra cita agendada.',
        operationId: 'businessAppointmentsStore',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos de la cita.',
            content: new OA\JsonContent(ref: AppointmentRequest::class),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cita creada.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'El usuario todavía no tiene un negocio asociado.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos u horario ocupado.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);

        $appointment = $business->appointments()->create($request->validated() + [
            'status' => $request->input('status', AppointmentStatus::Scheduled->value),
            'user_id' => $business->clients()->find($request->input('client_id'))?->user_id,
            'created_by_user_id' => $request->user()->getKey(),
        ]);

        $appointment->load('client');

        return response()->json([
            'message' => 'Cita agendada correctamente.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    #[OA\Put(
        path: '/business/appointments/{appointment}',
        tags: ['Agenda del negocio'],
        summary: 'Actualizar una cita',
        operationId: 'businessAppointmentsUpdate',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos a actualizar.',
            content: new OA\JsonContent(ref: AppointmentRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cita actualizada.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Datos inválidos u horario ocupado.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($appointment, $business);

        $appointment->update($request->validated());
        $appointment->load('client');

        return response()->json([
            'message' => 'Cita actualizada correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    #[OA\Patch(
        path: '/business/appointments/{appointment}/status',
        tags: ['Agenda del negocio'],
        summary: 'Cambiar el estado de una cita',
        description: 'Permite confirmar, completar, cancelar o marcar como no asistió. Al cancelar se registra cancelled_at y el motivo es obligatorio.',
        operationId: 'businessAppointmentsUpdateStatus',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Nuevo estado de la cita.',
            content: new OA\JsonContent(ref: AppointmentStatusRequest::class),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado.', content: new OA\JsonContent(ref: AppointmentResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
            new OA\Response(response: 422, description: 'Estado inválido o falta el motivo de cancelación.', content: new OA\JsonContent(ref: ValidationError::class)),
        ],
    )]
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($appointment, $business);

        $data = $request->validated();
        $status = AppointmentStatus::from($data['status']);

        $data['cancelled_at'] = $status === AppointmentStatus::Cancelled ? now() : null;

        if ($status !== AppointmentStatus::Cancelled) {
            unset($data['cancel_reason']);
        }

        $appointment->update($data);
        $appointment->load('client');

        return response()->json([
            'message' => 'Estado de la cita actualizado correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    #[OA\Delete(
        path: '/business/appointments/{appointment}',
        tags: ['Agenda del negocio'],
        summary: 'Eliminar una cita',
        operationId: 'businessAppointmentsDestroy',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cita eliminada.', content: new OA\JsonContent(ref: MessageResponse::class)),
            new OA\Response(response: 401, description: 'Token ausente, inválido o expirado.', content: new OA\JsonContent(ref: UnauthenticatedError::class)),
            new OA\Response(response: 403, description: 'El usuario no tiene el rol negocio.', content: new OA\JsonContent(ref: ForbiddenError::class)),
            new OA\Response(response: 404, description: 'La cita no existe o pertenece a otro negocio.', content: new OA\JsonContent(ref: NotFoundError::class)),
        ],
    )]
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        /** @var Business $business */
        $business = $this->currentBusiness($request);
        $this->assertBelongsToBusiness($appointment, $business);

        $appointment->delete();

        return response()->json([
            'message' => 'Cita eliminada correctamente.',
        ]);
    }
}
