<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Actualización de los datos de un negocio desde administración.
 */
class UpdateBusinessRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del negocio es obligatorio.',
            'name.max' => 'El nombre del negocio no puede tener más de :max caracteres.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',
            'address.max' => 'La dirección no puede tener más de :max caracteres.',
            'city.max' => 'La ciudad no puede tener más de :max caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del negocio',
            'description' => 'descripción',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'city' => 'ciudad',
        ];
    }
}
