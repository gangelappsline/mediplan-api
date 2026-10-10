<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Cliente del directorio de un negocio.
 *
 * Puede existir sin usuario de plataforma (alta manual) o estar vinculado a
 * una cuenta `client` mediante user_id.
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'email',
        'phone',
        'birth_date',
        'notes',
        'status',
        'last_appointment_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'birth_date' => 'date',
            'last_appointment_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Lead que originó al cliente, si se convirtió desde el embudo.
     *
     * @return HasOne<Lead, $this>
     */
    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'converted_client_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ClientStatus::Active);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $resolved = ClientStatus::coerce($status);

        if ($resolved !== null) {
            $query->where('status', $resolved);
        }
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }
}
