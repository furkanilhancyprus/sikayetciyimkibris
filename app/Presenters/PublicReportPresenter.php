<?php

namespace App\Presenters;

use App\Models\CorruptionReport;

class PublicReportPresenter
{
    public function __construct(private readonly CorruptionReport $report) {}

    public function title(): string
    {
        return $this->report->title;
    }

    public function body(): string
    {
        return $this->report->body;
    }

    public function reporterName(): ?string
    {
        if (! $this->report->identity_disclosed || blank($this->report->disclosure_consent_at)) {
            return null;
        }

        return $this->report->reporter_name;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'reporter_name' => $this->reporterName(),
            'published_at' => $this->report->published_at,
        ];
    }
}
