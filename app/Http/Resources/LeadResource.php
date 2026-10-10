<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lead
 */
class LeadResource extends JsonResource
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
            'company' => $this->company,
            'source' => $this->source,
            'status' => [
                'name' => $this->status?->value,
                'label' => $this->status?->label(),
                'is_open' => $this->status?->isOpen(),
            ],
            'estimated_value' => $this->estimated_value !== null ? (float) $this->estimated_value : null,
            'notes' => $this->notes,
            'assigned_to' => $this->whenLoaded(
                'assignedTo',
                fn () => $this->assignedTo !== null ? new UserResource($this->assignedTo) : null,
            ),
            'follow_up_at' => $this->follow_up_at?->toISOString(),
            'contacted_at' => $this->contacted_at?->toISOString(),
            'converted_client' => $this->whenLoaded(
                'convertedClient',
                fn () => $this->convertedClient !== null ? new ClientResource($this->convertedClient) : null,
            ),
            'converted_at' => $this->converted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
