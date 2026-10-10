<?php

namespace App\Http\Requests\Business;

use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(LeadStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido. Valores permitidos: '.implode(', ', LeadStatus::values()).'.',
            'notes.max' => 'Las notas no pueden tener más de :max caracteres.',
            'follow_up_at.date' => 'La fecha de seguimiento no es válida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'notes' => 'notas',
            'follow_up_at' => 'fecha de seguimiento',
        ];
    }
}
