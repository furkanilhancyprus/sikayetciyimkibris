<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    protected $fillable = [
        'entity_id',
        'invited_email',
        'invited_email_hash',
        'contact_name',
        'token',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'last_sent_at',
        'accepted_user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OrganizationInvitation $invitation): void {
            $invitation->token ??= Str::random(64);
            $invitation->expires_at ??= now()->addDays((int) config('security.organization_invitation_expiry_days', 14));

            if (filled($invitation->invited_email)) {
                $invitation->invited_email_hash = hash('sha256', mb_strtolower($invitation->invited_email));
            }
        });

        static::saving(function (OrganizationInvitation $invitation): void {
            if ($invitation->isDirty('invited_email') && filled($invitation->invited_email)) {
                $invitation->invited_email_hash = hash('sha256', mb_strtolower($invitation->invited_email));
            }
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'loggable');
    }

    public function isUsable(): bool
    {
        return blank($this->accepted_at)
            && blank($this->revoked_at)
            && (blank($this->expires_at) || $this->expires_at->isFuture());
    }
}
