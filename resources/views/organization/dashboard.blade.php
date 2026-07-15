@extends('layouts.public')

@section('title', 'Kurum Paneli - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="org-shell">
        <div class="org-hero">
            <div>
                <p class="eyebrow">Kurum paneli</p>
                <h1>{{ $entity?->name ?? 'Kurum hesabı' }}</h1>
                <p class="lead">Kurumunuza yönlendirilen başvuruları takip edin, cevapları moderasyona gönderin ve cevapsız dosyaları önceliklendirin.</p>
            </div>
            <a class="button button-primary" href="{{ route('organization-portal.reports.index') }}">Başvuruları Gör</a>
        </div>

        <div class="org-stats">
            <a href="{{ route('organization-portal.reports.index') }}">
                <strong>{{ $totalReports }}</strong>
                <span>Toplam başvuru</span>
            </a>
            <a href="{{ route('organization-portal.reports.index', ['status' => 'unanswered']) }}">
                <strong>{{ $unansweredReports }}</strong>
                <span>Cevap bekleyen</span>
            </a>
            <a href="{{ route('organization-portal.reports.index', ['status' => 'pending_response']) }}">
                <strong>{{ $pendingResponses }}</strong>
                <span>Onay bekleyen cevap</span>
            </a>
        </div>

        <section class="org-list-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Son gelenler</p>
                    <h2>Yeni başvurular</h2>
                </div>
                <a href="{{ route('organization-portal.reports.index') }}">Tümünü gör</a>
            </div>

            <div class="org-report-list">
                @forelse ($latestReports as $report)
                    <a class="org-report-row" href="{{ route('organization-portal.reports.show', $report) }}">
                        <div>
                            <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                            <strong>{{ $report->title }}</strong>
                            <small>{{ $report->region?->name ?? 'Bölge belirtilmedi' }} · {{ $report->created_at->format('d.m.Y H:i') }}</small>
                        </div>
                        <code>{{ $report->tracking_code }}</code>
                    </a>
                @empty
                    <div class="empty-feed">
                        <strong>Henüz kurumunuza yönlendirilen başvuru yok.</strong>
                        <span>Yeni başvurular geldikçe burada görünecek.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </section>
@endsection
