<?php

namespace App\Enums;

/**
 * Estados del embudo de leads de un negocio.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * Etiqueta en español mostrada en la API.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Contacted => 'Contactado',
            self::Qualified => 'Calificado',
            self::Proposal => 'Propuesta enviada',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    /**
     * El lead todavía puede trabajarse (no está cerrado).
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * Estados que se consideran abiertos.
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return array_values(array_filter(
            self::values(),
            fn (string $value) => self::from($value)->isOpen(),
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
