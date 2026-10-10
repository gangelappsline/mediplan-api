<?php

namespace App\Http\Requests\Business;

use App\Enums\ClientStatus;
use App\Http\Requests\Concerns\InteractsWithCurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    use InteractsWithCurrentBusiness;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $businessId = $this->currentBusinessId();

        return [
            'name' => ['required', 'string', 'max:180'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->where('business_id', $businessId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(ClientStatus::values())],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'name.max' => 'El nombre del cliente no puede tener más de :max caracteres.',

            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.unique' => 'Ya existe un cliente con este correo electrónico en tu negocio.',

            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',

            'birth_date.date' => 'La fecha de nacimiento no es válida.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',

            'notes.max' => 'Las notas no pueden tener más de :max caracteres.',

            'status.in' => 'El estado no es válido. Valores permitidos: '.implode(', ', ClientStatus::values()).'.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
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
            'birth_date' => 'fecha de nacimiento',
            'notes' => 'notas',
            'status' => 'estado',
            'user_id' => 'usuario',
        ];
    }
}
