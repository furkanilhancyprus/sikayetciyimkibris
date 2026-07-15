<?php

namespace App\Models;

use App\Enums\CorruptionReportStatus;
use App\Exceptions\InvalidReportTransition;
use App\States\CorruptionReportStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class CorruptionReport extends Model
{
    protected $fillable = [
        'tracking_code',
        'intake_type',
        'issue_area',
        'entity_id',
        'region_id',
        'title',
        'body',
        'public_body',
        'reporter_name',
        'reporter_contact',
        'identity_disclosed',
        'disclosure_consent_at',
        'disclosure_consent_text',
        'status',
        'assigned_reporter_id',
        'editor_approved_by',
        'editor_approved_at',
        'legal_approved_by',
        'legal_approved_at',
        'rejected_reason',
        'published_at',
        'solution_status',
        'solution_feedback',
        'solution_feedback_by',
        'solution_feedback_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'reporter_name' => 'encrypted',
            'reporter_contact' => 'encrypted',
            'identity_disclosed' => 'boolean',
            'disclosure_consent_at' => 'datetime',
            'status' => CorruptionReportStatus::class,
            'editor_approved_at' => 'datetime',
            'legal_approved_at' => 'datetime',
            'published_at' => 'datetime',
            'solution_feedback_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CorruptionReport $report): void {
            $report->tracking_code ??= self::newTrackingCode();
            $report->status ??= CorruptionReportStatus::Submitted;
        });

        static::saving(function (CorruptionReport $report): void {
            if ($report->status === CorruptionReportStatus::Published && ! $report->hasPublicationApprovals()) {
                throw new InvalidReportTransition('A corruption report cannot be published without editor and legal approvals.');
            }

            if ($report->status === CorruptionReportStatus::Published && blank($report->public_body)) {
                throw new InvalidReportTransition('A redacted public body is required before publishing a corruption report.');
            }
        });
    }

    public static function newTrackingCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (self::query()->where('tracking_code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'tracking_code';
    }

    public function hasPublicationApprovals(): bool
    {
        return filled($this->editor_approved_by)
            && filled($this->editor_approved_at)
            && filled($this->legal_approved_by)
            && filled($this->legal_approved_at);
    }

    public function startReview(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->startReview($this, $actor, $reason);
    }

    public function requestMoreInfo(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->requestMoreInfo($this, $actor, $reason);
    }

    public function approveByEditor(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->approveByEditor($this, $actor, $reason);
    }

    public function approveByLegal(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->approveByLegal($this, $actor, $reason);
    }

    public function publish(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->publish($this, $actor, $reason);
    }

    public function reject(User $actor, string $reason): void
    {
        app(CorruptionReportStateMachine::class)->reject($this, $actor, $reason);
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ReportMessage::class);
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'loggable');
    }

    public function assignedReporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reporter_id');
    }

    public function solutionFeedbackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solution_feedback_by');
    }
}
