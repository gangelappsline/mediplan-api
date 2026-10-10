<?php

namespace App\Http\Requests\Business;

use App\Enums\LeadStatus;
use App\Http\Requests\Concerns\InteractsWithCurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:180'],
            'source' => ['nullable', 'string', 'max:60'],
            'status' => ['sometimes', Rule::in(LeadStatus::values())],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Un lead necesita al menos una forma de contacto.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (blank($this->input('email')) && blank($this->input('phone'))) {
                $validator->errors()->add(
                    'email',
                    'Debes proporcionar al menos un correo electrónico o un teléfono.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del lead es obligatorio.',
            'name.max' => 'El nombre del lead no puede tener más de :max caracteres.',

            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede tener más de :max caracteres.',

            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',
            'company.max' => 'La empresa no puede tener más de :max caracteres.',
            'source.max' => 'El origen no puede tener más de :max caracteres.',

            'status.in' => 'El estado no es válido. Valores permitidos: '.implode(', ', LeadStatus::values()).'.',

            'estimated_value.numeric' => 'El valor estimado debe ser un número.',
            'estimated_value.min' => 'El valor estimado no puede ser negativo.',
            'estimated_value.max' => 'El valor estimado no puede superar :max.',

            'notes.max' => 'Las notas no pueden tener más de :max caracteres.',

            'assigned_to_user_id.exists' => 'El usuario asignado no existe.',
            'follow_up_at.date' => 'La fecha de seguimiento no es válida.',
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
            'company' => 'empresa',
            'source' => 'origen',
            'status' => 'estado',
            'estimated_value' => 'valor estimado',
            'notes' => 'notas',
            'assigned_to_user_id' => 'usuario asignado',
            'follow_up_at' => 'fecha de seguimiento',
        ];
    }
}
