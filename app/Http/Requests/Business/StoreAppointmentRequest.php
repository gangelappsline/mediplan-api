<?php

namespace App\Http\Requests\Business;

use App\Enums\AppointmentStatus;
use App\Http\Requests\Concerns\InteractsWithCurrentBusiness;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    use InteractsWithCurrentBusiness;

    /**
     * Estados permitidos al crear una cita.
     *
     * @var list<string>
     */
    public const CREATABLE_STATUSES = [
        AppointmentStatus::Scheduled->value,
        AppointmentStatus::Confirmed->value,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('business_id', $this->currentBusinessId()),
            ],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', Rule::in(self::CREATABLE_STATUSES)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * Evita agendar dos citas que se traslapen en el mismo negocio.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $businessId = $this->currentBusinessId();
            $startsAt = $this->date('starts_at');
            $endsAt = $this->date('ends_at');

            if ($businessId === null || $startsAt === null || $endsAt === null) {
                return;
            }

            $overlaps = Appointment::query()
                ->where('business_id', $businessId)
                ->whereIn('status', AppointmentStatus::bookedValues())
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->when($this->route('appointment'), fn ($query, $id) => $query->whereKeyNot($id))
                ->exists();

            if ($overlaps) {
                $validator->errors()->add(
                    'starts_at',
                    'Ya existe una cita agendada en ese horario.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.integer' => 'El cliente seleccionado no es válido.',
            'client_id.exists' => 'El cliente seleccionado no pertenece a tu negocio.',

            'title.required' => 'El título de la cita es obligatorio.',
            'title.max' => 'El título no puede tener más de :max caracteres.',

            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'starts_at.required' => 'La fecha y hora de inicio es obligatoria.',
            'starts_at.date' => 'La fecha y hora de inicio no es válida.',
            'starts_at.after' => 'La cita debe agendarse en una fecha futura.',

            'ends_at.required' => 'La fecha y hora de fin es obligatoria.',
            'ends_at.date' => 'La fecha y hora de fin no es válida.',
            'ends_at.after' => 'La hora de fin debe ser posterior a la hora de inicio.',

            'status.in' => 'Al crear una cita solo puedes usar los estados: '.implode(', ', self::CREATABLE_STATUSES).'.',

            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'title' => 'título',
            'description' => 'descripción',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'status' => 'estado',
            'price' => 'precio',
        ];
    }
}
