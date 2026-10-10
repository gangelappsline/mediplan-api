<?php

namespace App\Services\Leads;

use App\Enums\ClientStatus;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convierte un lead en un cliente del directorio del negocio.
 */
class LeadConverter
{
    /**
     * @param  array<string, mixed>  $overrides  Datos opcionales que pisan los del lead.
     *
     * @throws ValidationException cuando el lead ya fue convertido.
     */
    public function convert(Lead $lead, array $overrides = []): Client
    {
        if ($lead->converted_client_id !== null) {
            throw ValidationException::withMessages([
                'lead' => 'Este lead ya fue convertido en cliente.',
            ]);
        }

        return DB::transaction(function () use ($lead, $overrides): Client {
            /** @var Client $client */
            $client = $lead->business->clients()->create([
                'name' => $overrides['name'] ?? $lead->name,
                'email' => $overrides['email'] ?? $lead->email,
                'phone' => $overrides['phone'] ?? $lead->phone,
                'notes' => $overrides['notes'] ?? $lead->notes,
                'status' => ClientStatus::Active,
            ]);

            $lead->forceFill([
                'converted_client_id' => $client->getKey(),
                'converted_at' => now(),
                'status' => LeadStatus::Won,
            ])->save();

            return $client;
        });
    }
}
