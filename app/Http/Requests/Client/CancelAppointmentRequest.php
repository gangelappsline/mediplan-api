<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelación de una cita desde el panel del cliente.
 */
class CancelAppointmentRequest extends FormRequest
{
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
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancel_reason.max' => 'El motivo no puede tener más de :max caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cancel_reason' => 'motivo de cancelación',
        ];
    }
}
