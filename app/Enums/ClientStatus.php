<?php

namespace App\Enums;

/**
 * Estados de un cliente dentro del directorio de un negocio.
 */
enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Etiqueta en español mostrada en la API.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
        };
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
