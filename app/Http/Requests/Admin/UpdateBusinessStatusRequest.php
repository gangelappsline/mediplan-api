<?php

namespace App\Http\Requests\Admin;

use App\Enums\BusinessStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aprueba, suspende o regresa a pendiente un negocio.
 */
class UpdateBusinessStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(BusinessStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido. Valores permitidos: '.implode(', ', BusinessStatus::values()).'.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
        ];
    }
}
