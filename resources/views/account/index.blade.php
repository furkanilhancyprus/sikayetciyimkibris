@extends('layouts.public')

@section('title', 'Hesabım - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="complaints-hero account-hero">
        <div>
            <p class="eyebrow">Hesabım</p>
            <h1>Başvurularını tek yerden takip et.</h1>
            <p class="lead">Hesabınla gönderdiğin itiraz ve ihbarların durumunu, konusunu ve takip kodunu burada görebilirsin.</p>
        </div>
        <a class="button button-primary" href="{{ route('reports.create') }}">+ Yeni İtiraz Oluştur</a>
    </section>

    <section class="account-list">
        @forelse ($reports as $report)
            <a class="account-report-card" href="{{ route('account.reports.show', $report) }}">
                <div>
                    <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                    <strong>{{ $report->title }}</strong>
                    <small>{{ $report->entity?->name ?? $report->region?->name ?? 'Kurum/bölge belirtilmedi' }}</small>
                </div>
                <div class="account-report-meta">
                    <code>{{ $report->tracking_code }}</code>
                    <span>{{ \App\Filament\Resources\CorruptionReportResource::statusLabels()[$report->status->value] ?? $report->status->value }}</span>
                </div>
            </a>
        @empty
            <div class="empty-feed">
                <strong>Henüz hesabına bağlı başvuru yok.</strong>
                <span>Yeni başvuru oluştururken giriş yapmış olursan burada görünecek.</span>
            </div>
        @endforelse
    </section>
@endsection
