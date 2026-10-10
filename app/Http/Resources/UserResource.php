<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'name' => $role->name,
                'label' => $role->label,
            ])->values()->all(), []),
            'business' => $this->whenLoaded('business', fn () => $this->business !== null
                ? new BusinessResource($this->business)
                : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
