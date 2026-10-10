<?php

namespace App\Http\Requests\Business;

use Illuminate\Validation\Rules\Unique;

/**
 * Actualización parcial de un cliente del directorio del negocio.
 */
class UpdateClientRequest extends StoreClientRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'][0] = 'sometimes';
        $rules['email'] = array_map(
            fn ($rule) => $rule instanceof Unique
                ? $rule->ignore($this->route('client'))
                : $rule,
            $rules['email'],
        );

        return $rules;
    }
}
