<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Validation\Rule;

/**
 * Actualización parcial de un usuario desde el panel de administración.
 */
class UpdateUserRequest extends StoreUserRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'][0] = 'sometimes';
        $rules['email'] = ['sometimes', 'required', 'string', 'email', 'max:255',
            Rule::unique('users', 'email')->ignore($this->route('user')),
        ];
        $rules['password'] = ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'];
        $rules['role'] = ['sometimes', Rule::in(RoleName::acceptableInputs())];
        $rules['business_name'] = ['nullable', 'string', 'max:180'];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'password.nullable' => 'La contraseña no es válida.',
        ];
    }
}
