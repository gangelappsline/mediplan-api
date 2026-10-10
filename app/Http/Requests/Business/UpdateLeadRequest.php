<?php

namespace App\Http\Requests\Business;

/**
 * Actualización parcial de un lead.
 *
 * No exige de nuevo un medio de contacto porque el lead ya fue validado al crearse.
 */
class UpdateLeadRequest extends StoreLeadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'][0] = 'sometimes';

        return $rules;
    }

    public function withValidator($validator): void
    {
        // La actualización parcial no vuelve a exigir correo o teléfono.
    }
}
