<?php

namespace App\Http\Resources;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessSetting
 */
class BusinessSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'timezone' => $this->timezone,
            'appointment_duration_minutes' => (int) $this->appointment_duration_minutes,
            'slot_interval_minutes' => (int) $this->slot_interval_minutes,
            'min_notice_minutes' => (int) $this->min_notice_minutes,
            'max_advance_days' => (int) $this->max_advance_days,
            'working_hours' => $this->working_hours,
            'auto_confirm_appointments' => (bool) $this->auto_confirm_appointments,
            'allow_online_booking' => (bool) $this->allow_online_booking,
            'currency' => $this->currency,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
