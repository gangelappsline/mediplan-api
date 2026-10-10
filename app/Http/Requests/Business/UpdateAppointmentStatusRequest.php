<?php

namespace App\Http\Requests\Business;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    /**
     * Transiciones permitidas desde la agenda.
     *
     * @var list<string>
     */
    public const TRANSITIONS = [
        AppointmentStatus::Confirmed->value,
        AppointmentStatus::Completed->value,
        AppointmentStatus::Cancelled->value,
        AppointmentStatus::NoShow->value,
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
            'status' => ['required', Rule::in(self::TRANSITIONS)],
            'cancel_reason' => ['nullable', 'string', 'max:255', 'required_if:status,'.AppointmentStatus::Cancelled->value],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido. Valores permitidos: '.implode(', ', self::TRANSITIONS).'.',

            'cancel_reason.required_if' => 'Indica el motivo de la cancelación.',
            'cancel_reason.max' => 'El motivo no puede tener más de :max caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'cancel_reason' => 'motivo de cancelación',
        ];
    }
}
