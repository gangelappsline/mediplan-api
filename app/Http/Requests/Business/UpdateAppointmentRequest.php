<?php

namespace App\Http\Requests\Business;

/**
 * Actualización parcial de una cita de la agenda.
 */
class UpdateAppointmentRequest extends StoreAppointmentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['client_id'][0] = 'sometimes';
        $rules['title'][0] = 'sometimes';
        $rules['starts_at'] = ['sometimes', 'required', 'date'];
        $rules['ends_at'] = ['sometimes', 'required', 'date', 'after:starts_at'];

        return $rules;
    }
}
