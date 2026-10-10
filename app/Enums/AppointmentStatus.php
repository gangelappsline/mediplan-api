<?php

namespace App\Enums;

/**
 * Estados de una cita de la agenda de un negocio.
 */
enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /**
     * Etiqueta en español mostrada en la API.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::Confirmed => 'Confirmada',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'No asistió',
        };
    }

    /**
     * La cita sigue ocupando un lugar en la agenda.
     */
    public function isBooked(): bool
    {
        return in_array($this, [self::Scheduled, self::Confirmed], true);
    }

    /**
     * Estados que siguen ocupando la agenda.
     *
     * @return array<int, string>
     */
    public static function bookedValues(): array
    {
        return array_values(array_filter(
            self::values(),
            fn (string $value) => self::from($value)->isBooked(),
        ));
    }

    /**
     * @return array<int, string> Valores guardados en base de datos.
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }

    /**
     * Resuelve el enum desde su valor en inglés o su etiqueta en español.
     */
    public static function coerce(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        foreach (self::cases() as $case) {
            if ($normalized === $case->value || $normalized === strtolower($case->label())) {
                return $case;
            }
        }

        return null;
    }
}
