<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Activa o desactiva la cuenta de un usuario.
 */
class UpdateUserStatusRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_active.required' => 'Indica si la cuenta debe estar activa.',
            'is_active.boolean' => 'El valor de is_active debe ser verdadero o falso.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_active' => 'estado de la cuenta',
        ];
    }
}
