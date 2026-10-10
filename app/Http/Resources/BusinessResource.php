<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Business
 */
class BusinessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'status' => [
                'name' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'owner' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'clients_count' => $this->whenCounted('clients'),
            'leads_count' => $this->whenCounted('leads'),
            'appointments_count' => $this->whenCounted('appointments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
