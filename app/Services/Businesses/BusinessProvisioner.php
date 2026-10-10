<?php

namespace App\Services\Businesses;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Crea el negocio y su configuración por defecto para un usuario `business`.
 */
class BusinessProvisioner
{
    /**
     * Devuelve el negocio del usuario, creándolo si todavía no existe.
     */
    public function provisionFor(User $user, ?string $name = null, BusinessStatus $status = BusinessStatus::Active): Business
    {
        $existing = $user->business;

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $name, $status): Business {
            /** @var Business $business */
            $business = Business::query()->create([
                'user_id' => $user->getKey(),
                'name' => $name ?: $user->name,
                'email' => $user->email,
                'status' => $status,
            ]);

            $business->setting()->create(BusinessSetting::defaults());

            return $business;
        });
    }
}
