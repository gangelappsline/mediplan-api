<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Application role (client, business, admin).
 *
 * The `name` column stores the English value (see \App\Enums\RoleName),
 * while `label` holds the Spanish display name.
 */
class Role extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    /**
     * Users that have this role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
