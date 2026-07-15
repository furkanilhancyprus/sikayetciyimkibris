@extends('layouts.public')

@section('title', 'Başvuru Durumu - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="result-page">
        <div class="result-card wide">
            <span class="result-icon">i</span>
            <p class="eyebrow">Başvuru durumu</p>
            <h1>{{ $publicStatus }}</h1>
            <p>Dosya durumunda değişiklik olduğunda takip kodunla tekrar sorgulayabilirsin.</p>

            <dl class="detail-meta">
                <div>
                    <dt>Takip Kodu</dt>
                    <dd>{{ $report->tracking_code }}</dd>
                </div>
                <div>
                    <dt>Tür</dt>
                    <dd>{{ $report->intake_type === 'complaint' ? 'Şikâyet' : 'İhbar' }}</dd>
                </div>
                <div>
                    <dt>Konu</dt>
                    <dd>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</dd>
                </div>
                <div>
                    <dt>Bölge</dt>
                    <dd>{{ $report->region?->name ?? 'Belirtilmedi' }}</dd>
                </div>
                <div>
                    <dt>Kurum / Şirket</dt>
                    <dd>{{ $report->entity?->name ?? 'Belirtilmedi' }}</dd>
                </div>
                <div>
                    <dt>Başlık</dt>
                    <dd>{{ $report->title }}</dd>
                </div>
            </dl>

            <div class="result-actions">
                <a class="button button-secondary" href="{{ route('reports.track-form') }}">Yeni Sorgu Yap</a>
                @if ($report->status === \App\Enums\CorruptionReportStatus::Published)
                    <a class="button button-primary" href="{{ route('reports.show', $report) }}">Yayın Sayfasını Aç</a>
                @endif
            </div>
        </div>
    </section>
@endsection
