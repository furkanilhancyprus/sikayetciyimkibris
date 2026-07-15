<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReportMessage extends Model
{
    protected $fillable = [
        'corruption_report_id',
        'sender_type',
        'user_id',
        'body',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'reviewed_at' => 'datetime',
        ];
    }

    public function corruptionReport(): BelongsTo
    {
        return $this->belongsTo(CorruptionReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'loggable');
    }

    public function approve(User $actor, string $note): void
    {
        $this->forceFill([
            'status' => 'approved',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->moderationLogs()->create([
            'actor_id' => $actor->id,
            'action' => 'organization_response_approved',
            'reason' => $note,
        ]);
    }

    public function reject(User $actor, string $note): void
    {
        $this->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->moderationLogs()->create([
            'actor_id' => $actor->id,
            'action' => 'organization_response_rejected',
            'reason' => $note,
        ]);
    }
}
