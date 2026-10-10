<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Negocio registrado en la plataforma.
 *
 * Cada negocio pertenece a un usuario con rol `business` y concentra su
 * directorio de clientes, sus leads, su agenda y su configuración.
 */
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
        ];
    }

    /**
     * Usuario propietario del negocio.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Configuración operativa del negocio.
     *
     * @return HasOne<BusinessSetting, $this>
     */
    public function setting(): HasOne
    {
        return $this->hasOne(BusinessSetting::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Devuelve la configuración del negocio creándola con valores por defecto
     * la primera vez que se solicita.
     */
    public function settings(): BusinessSetting
    {
        return $this->setting ?? $this->setting()->create(BusinessSetting::defaults());
    }

    /**
     * Solo negocios operativos.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', BusinessStatus::Active);
    }

    /**
     * Filtra por estado aceptando el valor en inglés o la etiqueta en español.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $resolved = BusinessStatus::coerce($status);

        if ($resolved !== null) {
            $query->where('status', $resolved);
        }
    }

    /**
     * Búsqueda libre por nombre, correo, teléfono o ciudad.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($term): void {
            $like = '%'.$term.'%';

            $inner->where('name', 'like', $term === '' ? $term : $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('city', 'like', $like);
        });
    }
}
