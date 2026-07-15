@extends('layouts.public')

@section('title', 'Kurum Başvuruları - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="org-shell">
        <div class="org-hero compact">
            <div>
                <p class="eyebrow">Kurum paneli</p>
                <h1>Başvurular</h1>
            </div>
            <a class="button button-secondary" href="{{ route('organization-portal.dashboard') }}">Panele Dön</a>
        </div>

        <nav class="org-filters" aria-label="Başvuru filtreleri">
            <a class="{{ $status === '' ? 'is-active' : '' }}" href="{{ route('organization-portal.reports.index') }}">Tümü</a>
            <a class="{{ $status === 'unanswered' ? 'is-active' : '' }}" href="{{ route('organization-portal.reports.index', ['status' => 'unanswered']) }}">Cevapsız</a>
            <a class="{{ $status === 'pending_response' ? 'is-active' : '' }}" href="{{ route('organization-portal.reports.index', ['status' => 'pending_response']) }}">Onay bekleyen cevaplar</a>
        </nav>

        <div class="org-report-list">
            @forelse ($reports as $report)
                <a class="org-report-row" href="{{ route('organization-portal.reports.show', $report) }}">
                    <div>
                        <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                        <strong>{{ $report->title }}</strong>
                        <small>{{ $report->region?->name ?? 'Bölge belirtilmedi' }} · {{ $report->created_at->format('d.m.Y H:i') }}</small>
                    </div>
                    <div class="org-row-meta">
                        <code>{{ $report->tracking_code }}</code>
                        <span>{{ \App\Filament\Resources\CorruptionReportResource::statusLabels()[$report->status->value] ?? $report->status->value }}</span>
                    </div>
                </a>
            @empty
                <div class="empty-feed">
                    <strong>Bu filtrede başvuru yok.</strong>
                    <span>Filtreyi değiştirerek diğer başvuruları görüntüleyebilirsiniz.</span>
                </div>
            @endforelse
        </div>
    </section>
@endsection
