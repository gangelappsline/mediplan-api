<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cita de la agenda de un negocio.
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'client_id',
        'user_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'price',
        'cancelled_at',
        'cancel_reason',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'price' => 'decimal:2',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Usuario de plataforma asociado a la cita, cuando el cliente tiene cuenta.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La cita sigue vigente en la agenda.
     */
    public function isBooked(): bool
    {
        return $this->status?->isBooked() ?? false;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $resolved = AppointmentStatus::coerce($status);

        if ($resolved !== null) {
            $query->where('status', $resolved);
        }
    }

    /**
     * Citas que siguen ocupando la agenda.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBooked(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::bookedValues());
    }

    /**
     * Citas dentro de un rango de fechas (inclusivo).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBetweenDates(Builder $query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from !== null) {
            $query->where('starts_at', '>=', $from->copy()->startOfDay());
        }

        if ($to !== null) {
            $query->where('starts_at', '<=', $to->copy()->endOfDay());
        }
    }

    /**
     * Citas futuras a partir de un instante dado.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeUpcoming(Builder $query, ?Carbon $from = null): void
    {
        $query->where('starts_at', '>=', $from ?? now());
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePast(Builder $query, ?Carbon $to = null): void
    {
        $query->where('starts_at', '<', $to ?? now());
    }
}
