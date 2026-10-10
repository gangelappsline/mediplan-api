<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSetting>
 */
class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'timezone' => 'America/Mexico_City',
            'appointment_duration_minutes' => 30,
            'slot_interval_minutes' => 30,
            'min_notice_minutes' => 60,
            'max_advance_days' => 30,
            'working_hours' => BusinessSetting::defaultWorkingHours(),
            'auto_confirm_appointments' => false,
            'allow_online_booking' => true,
            'currency' => 'MXN',
        ];
    }
}
