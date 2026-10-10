<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessProfileRequest extends FormRequest
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
            'name.string' => 'El nombre del negocio debe ser una cadena de texto.',
            'name.max' => 'El nombre del negocio no puede tener más de :max caracteres.',

            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede tener más de :max caracteres.',

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
