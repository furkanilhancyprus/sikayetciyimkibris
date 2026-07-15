@extends('layouts.public')

@section('title', $report->title.' - Hesabım')

@section('content')
    <section class="account-detail-shell">
        <div class="account-detail-header">
            <div>
                <p class="eyebrow">Başvuru detayı</p>
                <h1>{{ $report->title }}</h1>
                <div class="account-detail-tags">
                    <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                    <span>{{ \App\Filament\Resources\CorruptionReportResource::statusLabels()[$report->status->value] ?? $report->status->value }}</span>
                    <code>{{ $report->tracking_code }}</code>
                </div>
            </div>
            <a class="button button-secondary" href="{{ route('account.index') }}">Hesabıma Dön</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="account-detail-grid">
            <article class="account-detail-card">
                <h2>Başvuru Özeti</h2>
                <dl class="detail-meta compact">
                    <div>
                        <dt>Kurum</dt>
                        <dd>{{ $report->entity?->name ?? 'Belirtilmedi' }}</dd>
                    </div>
                    <div>
                        <dt>Bölge</dt>
                        <dd>{{ $report->region?->name ?? 'Belirtilmedi' }}</dd>
                    </div>
                    <div>
                        <dt>Gönderim</dt>
                        <dd>{{ $report->created_at->format('d.m.Y H:i') }}</dd>
                    </div>
                </dl>
                <p>{!! nl2br(e($report->public_body ?: $report->body)) !!}</p>
            </article>

            <aside class="account-detail-card">
                <h2>Ek Açıklama Gönder</h2>
                <p>Dosyana yeni bilgi, belge açıklaması veya kurum cevabına yanıt ekleyebilirsin. Mesajlar yayına alınmadan önce kontrol edilir.</p>
                <form class="message-form" method="POST" action="{{ route('account.reports.message', $report) }}">
                    @csrf
                    <label class="field">
                        Mesajın
                        <textarea name="body" required minlength="20" maxlength="4000" rows="6">{{ old('body') }}</textarea>
                        @error('body')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>
                    <button class="button button-primary" type="submit">Mesaj Gönder</button>
                </form>
            </aside>
        </div>

        <section class="account-thread">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Dosya geçmişi</p>
                    <h2>Mesajlar ve cevaplar</h2>
                </div>
            </div>

            @forelse ($report->messages->sortByDesc('created_at') as $message)
                <article class="thread-message {{ $message->sender_type === 'reporter' ? 'from-citizen' : 'from-organization' }}">
                    <div>
                        <strong>{{ $message->sender_type === 'reporter' ? 'Sen' : ($message->user?->name ?? $report->entity?->name ?? 'Kurum yetkilisi') }}</strong>
                        <span>
                            {{ match ($message->status) {
                                'pending' => 'Onay bekliyor',
                                'approved' => 'Yayında',
                                'rejected' => 'Reddedildi',
                                default => $message->status ?? 'Kaydedildi',
                            } }}
                        </span>
                        <time>{{ $message->created_at->format('d.m.Y H:i') }}</time>
                    </div>
                    <p>{!! nl2br(e($message->body)) !!}</p>
                </article>
            @empty
                <div class="empty-feed">
                    <strong>Henüz mesaj yok.</strong>
                    <span>Kurum cevapları ve senin ek açıklamaların burada görünecek.</span>
                </div>
            @endforelse
        </section>
    </section>
@endsection
