<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reemplaza los roles de un usuario.
 */
class UpdateUserRolesRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(RoleName::acceptableInputs())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Debes enviar al menos un rol.',
            'roles.array' => 'Los roles deben enviarse como un arreglo.',
            'roles.min' => 'Debes enviar al menos un rol.',
            'roles.*.required' => 'Cada rol enviado es obligatorio.',
            'roles.*.in' => 'Uno de los roles no es válido. Valores permitidos: '.implode(', ', RoleName::acceptableInputs()).'.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'roles' => 'roles',
            'roles.*' => 'rol',
        ];
    }
}
