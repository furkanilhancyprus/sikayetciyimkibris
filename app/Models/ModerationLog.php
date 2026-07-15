<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class ModerationLog extends Model
{
    protected $fillable = ['loggable_type', 'loggable_id', 'actor_id', 'action', 'reason'];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Moderation logs are immutable.'));
        static::deleting(fn (): never => throw new LogicException('Moderation logs are immutable.'));
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
