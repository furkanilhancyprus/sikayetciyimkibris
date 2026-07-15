<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Complaint extends Model
{
    protected $fillable = ['user_id', 'region_id', 'entity_id', 'type', 'title', 'body', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ComplaintVote::class);
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'loggable');
    }
}
