<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $fillable = ['name', 'category', 'region_id'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function corruptionReports(): HasMany
    {
        return $this->hasMany(CorruptionReport::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function organizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }
}
