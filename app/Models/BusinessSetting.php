<?php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración operativa de un negocio (agenda, horarios y moneda).
 */
class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'timezone',
        'appointment_duration_minutes',
        'slot_interval_minutes',
        'min_notice_minutes',
        'max_advance_days',
        'working_hours',
        'auto_confirm_appointments',
        'allow_online_booking',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_hours' => 'array',
            'auto_confirm_appointments' => 'boolean',
            'allow_online_booking' => 'boolean',
            'appointment_duration_minutes' => 'integer',
            'slot_interval_minutes' => 'integer',
            'min_notice_minutes' => 'integer',
            'max_advance_days' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Horario por defecto aplicado cuando el negocio no lo personaliza.
     *
     * @return array<string, array{open: string, close: string, closed: bool}>
     */
    public static function defaultWorkingHours(): array
    {
        $weekdays = [
            'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'saturday' => ['open' => '10:00', 'close' => '14:00', 'closed' => false],
            'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
        ];

        return $weekdays;
    }

    /**
     * Valores usados al crear la configuración de un negocio nuevo.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'timezone' => 'America/Mexico_City',
            'appointment_duration_minutes' => 30,
            'slot_interval_minutes' => 30,
            'min_notice_minutes' => 60,
            'max_advance_days' => 30,
            'working_hours' => self::defaultWorkingHours(),
            'auto_confirm_appointments' => false,
            'allow_online_booking' => true,
            'currency' => 'MXN',
        ];
    }
}
