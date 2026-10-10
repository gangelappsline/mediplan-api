<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de usuarios desde el panel de administración (incluye el rol admin).
 */
class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(RoleName::acceptableInputs())],
            'is_active' => ['sometimes', 'boolean'],
            'business_name' => ['nullable', 'string', 'max:180', 'required_if:role,business,negocio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de :max caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',

            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol seleccionado no es válido. Valores permitidos: '.implode(', ', RoleName::acceptableInputs()).'.',

            'business_name.required_if' => 'Indica el nombre del negocio cuando el rol es negocio.',
            'business_name.max' => 'El nombre del negocio no puede tener más de :max caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'password' => 'contraseña',
            'role' => 'rol',
            'is_active' => 'estado de la cuenta',
            'business_name' => 'nombre del negocio',
        ];
    }
}
