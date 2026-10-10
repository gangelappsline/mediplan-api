<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prospecto (lead) captado por un negocio.
 */
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'estimated_value',
        'notes',
        'assigned_to_user_id',
        'follow_up_at',
        'contacted_at',
        'converted_client_id',
        'converted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'estimated_value' => 'decimal:2',
            'follow_up_at' => 'datetime',
            'contacted_at' => 'datetime',
            'converted_at' => 'datetime',
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
     * Usuario responsable de dar seguimiento al lead.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Cliente generado al convertir el lead.
     *
     * @return BelongsTo<Client, $this>
     */
    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $resolved = LeadStatus::coerce($status);

        if ($resolved !== null) {
            $query->where('status', $resolved);
        }
    }

    /**
     * Leads que aún no están cerrados como ganados o perdidos.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', LeadStatus::openValues());
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeSource(Builder $query, ?string $source): void
    {
        $source = trim((string) $source);

        if ($source !== '') {
            $query->where('source', $source);
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
                ->orWhere('phone', 'like', $like)
                ->orWhere('company', 'like', $like);
        });
    }
}
