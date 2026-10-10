<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Negocio del que el usuario es propietario (rol business).
     *
     * @return HasOne<Business, $this>
     */
    public function business(): HasOne
    {
        return $this->hasOne(Business::class);
    }

    /**
     * Registros de cliente vinculados a la cuenta en los distintos negocios.
     *
     * @return HasMany<Client, $this>
     */
    public function clientProfiles(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Citas asociadas a la cuenta del usuario.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Roles assigned to the user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Assign one or more roles to the user without removing the existing ones.
     *
     * Missing roles are created automatically with their Spanish label,
     * so assignment never fails silently when seeders were not executed.
     *
     * @param  Role|RoleName|string  ...$roles  Role model, enum case or name (English or Spanish).
     */
    public function assignRole(Role|RoleName|string ...$roles): void
    {
        $this->roles()->syncWithoutDetaching($this->resolveRoleIds($roles));
    }

    /**
     * Replace every role of the user with the given ones.
     *
     * @param  Role|RoleName|string  ...$roles  Role model, enum case or name (English or Spanish).
     */
    public function syncRoles(Role|RoleName|string ...$roles): void
    {
        $this->roles()->sync($this->resolveRoleIds($roles));
    }

    /**
     * Determine if the user has the given role.
     *
     * @param  Role|RoleName|string  $role  Role model, enum case or name (English or Spanish).
     */
    public function hasRole(Role|RoleName|string $role): bool
    {
        $name = $this->normalizeRoleNames([$role])[0] ?? null;

        if ($name === null) {
            return false;
        }

        return $this->roles()->where('name', $name)->exists();
    }

    /**
     * Determine if the user has at least one of the given roles.
     *
     * @param  array<int, Role|RoleName|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $names = $this->normalizeRoleNames($roles);

        if ($names === []) {
            return false;
        }

        return $this->roles()->whereIn('name', $names)->exists();
    }

    /**
     * Resolve role identifiers to their database IDs, creating the
     * missing roles with their Spanish label.
     *
     * @param  array<int, Role|RoleName|string>  $roles
     * @return array<int, int>
     */
    protected function resolveRoleIds(array $roles): array
    {
        $ids = [];

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $found = $role->exists
                    ? $role
                    : Role::query()->where('name', $role->name)->first();

                if ($found !== null) {
                    $ids[] = $found->getKey();
                }

                continue;
            }

            $enum = $role instanceof RoleName
                ? $role
                : RoleName::coerce((string) $role);

            if ($enum === null) {
                continue;
            }

            $ids[] = Role::query()->firstOrCreate(
                ['name' => $enum->value],
                ['label' => $enum->label()],
            )->getKey();
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Normalize role identifiers to their English database values.
     *
     * @param  array<int, Role|RoleName|string>  $roles
     * @return array<int, string>
     */
    protected function normalizeRoleNames(array $roles): array
    {
        $names = [];

        foreach ($roles as $role) {
            $name = match (true) {
                $role instanceof Role => $role->name,
                $role instanceof RoleName => $role->value,
                default => RoleName::coerce((string) $role)?->value,
            };

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }
}
