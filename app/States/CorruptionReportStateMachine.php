<?php

namespace App\States;

use App\Enums\CorruptionReportStatus;
use App\Exceptions\InvalidReportTransition;
use App\Models\CorruptionReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CorruptionReportStateMachine
{
    public function startReview(CorruptionReport $report, User $actor, string $reason): void
    {
        $this->transition(
            $report,
            $actor,
            $reason,
            [CorruptionReportStatus::Submitted, CorruptionReportStatus::NeedsMoreInfo],
            CorruptionReportStatus::UnderReview,
            ['reporter', 'moderator', 'editor', 'admin'],
            'start_review',
        );
    }

    public function requestMoreInfo(CorruptionReport $report, User $actor, string $reason): void
    {
        $this->transition(
            $report,
            $actor,
            $reason,
            [CorruptionReportStatus::UnderReview],
            CorruptionReportStatus::NeedsMoreInfo,
            ['reporter', 'moderator', 'editor', 'admin'],
            'request_more_info',
        );
    }

    public function approveByEditor(CorruptionReport $report, User $actor, string $reason): void
    {
        $this->transition(
            $report,
            $actor,
            $reason,
            [CorruptionReportStatus::UnderReview],
            CorruptionReportStatus::EditorApproved,
            ['editor', 'admin'],
            'editor_approve',
            fn (CorruptionReport $report) => $report->forceFill([
                'editor_approved_by' => $actor->id,
                'editor_approved_at' => now(),
            ]),
        );
    }

    public function approveByLegal(CorruptionReport $report, User $actor, string $reason): void
    {
        $this->transition(
            $report,
            $actor,
            $reason,
            [CorruptionReportStatus::EditorApproved],
            CorruptionReportStatus::LegalApproved,
            ['legal', 'admin'],
            'legal_approve',
            fn (CorruptionReport $report) => $report->forceFill([
                'legal_approved_by' => $actor->id,
                'legal_approved_at' => now(),
            ]),
        );
    }

    public function publish(CorruptionReport $report, User $actor, string $reason): void
    {
        if (! $report->hasPublicationApprovals()) {
            throw new InvalidReportTransition('Publishing requires both editor and legal approvals.');
        }

        if (blank($report->public_body)) {
            throw new InvalidReportTransition('Publishing requires a reviewed and redacted public body.');
        }

        $this->transition(
            $report,
            $actor,
            $reason,
            [CorruptionReportStatus::LegalApproved],
            CorruptionReportStatus::Published,
            ['editor', 'admin'],
            'publish',
            fn (CorruptionReport $report) => $report->forceFill(['published_at' => now()]),
        );
    }

    public function reject(CorruptionReport $report, User $actor, string $reason): void
    {
        $this->transition(
            $report,
            $actor,
            $reason,
            [
                CorruptionReportStatus::Submitted,
                CorruptionReportStatus::UnderReview,
                CorruptionReportStatus::NeedsMoreInfo,
                CorruptionReportStatus::EditorApproved,
                CorruptionReportStatus::LegalApproved,
            ],
            CorruptionReportStatus::Rejected,
            ['editor', 'legal', 'admin'],
            'reject',
            fn (CorruptionReport $report) => $report->forceFill(['rejected_reason' => $reason]),
        );
    }

    /**
     * @param  array<int, CorruptionReportStatus>  $from
     * @param  array<int, string>  $roles
     */
    private function transition(
        CorruptionReport $report,
        User $actor,
        string $reason,
        array $from,
        CorruptionReportStatus $to,
        array $roles,
        string $action,
        ?callable $mutate = null,
    ): void {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidReportTransition('A reason is required for every status change.');
        }

        if (! $actor->hasAnyRole($roles)) {
            throw new InvalidReportTransition('The actor does not have permission for this report transition.');
        }

        if (! in_array($report->status, $from, true)) {
            throw new InvalidReportTransition("Cannot transition report from {$report->status->value} to {$to->value}.");
        }

        DB::transaction(function () use ($report, $actor, $reason, $to, $action, $mutate): void {
            $mutate?->call($this, $report);
            $report->forceFill(['status' => $to])->save();

            $report->moderationLogs()->create([
                'actor_id' => $actor->id,
                'action' => $action,
                'reason' => $reason,
            ]);
        });
    }
}
