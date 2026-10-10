<?php

namespace App\Enums;

/**
 * Estados posibles de un negocio dentro de la plataforma.
 *
 * El valor se guarda en inglés en la columna `businesses.status` y la
 * etiqueta en español se expone al frontend mediante label().
 */
enum BusinessStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * Etiqueta en español mostrada en la API.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Active => 'Activo',
            self::Suspended => 'Suspendido',
        };
    }

    /**
     * El negocio puede operar con normalidad.
     */
    public function isOperative(): bool
    {
        return $this === self::Active;
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
