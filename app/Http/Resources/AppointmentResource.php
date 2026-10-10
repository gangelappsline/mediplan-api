<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'title' => $this->title,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => [
                'name' => $this->status?->value,
                'label' => $this->status?->label(),
                'is_booked' => $this->status?->isBooked(),
            ],
            'price' => $this->price !== null ? (float) $this->price : null,
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'business' => $this->whenLoaded('business', fn () => new BusinessResource($this->business)),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
