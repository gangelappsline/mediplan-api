<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->toDateString(),
            'notes' => $this->notes,
            'status' => [
                'name' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'business' => $this->whenLoaded('business', fn () => new BusinessResource($this->business)),
            'appointments_count' => $this->whenCounted('appointments'),
            'last_appointment_at' => $this->last_appointment_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
