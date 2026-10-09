<?php

namespace App\Enums;

/**
 * Roles available in the application.
 *
 * The `value` is stored in the database (English), while the `label()`
 * and `spanishSlug()` methods expose the Spanish representation used
 * in API messages and requests.
 */
enum RoleName: string
{
    case Client = 'client';
    case Business = 'business';
    case Admin = 'admin';

    /**
     * Spanish label shown to API consumers.
     */
    public function label(): string
    {
        return match ($this) {
            self::Client => 'Cliente',
            self::Business => 'Negocio',
            self::Admin => 'Administrador',
        };
    }

    /**
     * Spanish slug accepted by the API (e.g. during registration).
     */
    public function spanishSlug(): string
    {
        return match ($this) {
            self::Client => 'cliente',
            self::Business => 'negocio',
            self::Admin => 'administrador',
        };
    }

    /**
     * Resolve a role from either its English database value
     * or its Spanish slug. Returns null when unknown.
     */
    public static function coerce(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        foreach (self::cases() as $case) {
            if ($normalized === $case->value || $normalized === $case->spanishSlug()) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Roles that are allowed to self-register through the API.
     *
     * @return array<int, string> Spanish slugs.
     */
    public static function registrable(): array
    {
        return [
            self::Client->spanishSlug(),
            self::Business->spanishSlug(),
        ];
    }

    /**
     * @return array<int, string> English database values.
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
