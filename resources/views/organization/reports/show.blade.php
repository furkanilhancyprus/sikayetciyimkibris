@extends('layouts.public')

@section('title', $report->title.' - Kurum Paneli')

@section('content')
    <section class="org-shell">
        <div class="org-hero compact">
            <div>
                <p class="eyebrow">Kurum başvurusu</p>
                <h1>{{ $report->title }}</h1>
                <div class="account-detail-tags">
                    <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                    <span>{{ \App\Filament\Resources\CorruptionReportResource::statusLabels()[$report->status->value] ?? $report->status->value }}</span>
                    <code>{{ $report->tracking_code }}</code>
                </div>
            </div>
            <a class="button button-secondary" href="{{ route('organization-portal.reports.index') }}">Listeye Dön</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="account-detail-grid">
            <article class="account-detail-card">
                <h2>Vatandaş Başvurusu</h2>
                <dl class="detail-meta compact">
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
                <h2>Kurum Cevabı Yaz</h2>
                <p>Cevabınız vatandaşa ve kamuya açık alana düşmeden önce platform ekibi tarafından kontrol edilir.</p>
                <form class="message-form" method="POST" action="{{ route('organization-portal.reports.respond', $report) }}">
                    @csrf
                    <label class="field">
                        Cevap metni
                        <textarea name="body" required minlength="30" maxlength="6000" rows="8">{{ old('body') }}</textarea>
                        @error('body')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>
                    <button class="button button-primary" type="submit">Cevabı Gönder</button>
                </form>
            </aside>
        </div>

        <section class="account-thread">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Dosya geçmişi</p>
                    <h2>Mesajlar</h2>
                </div>
            </div>

            @forelse ($report->messages->sortByDesc('created_at') as $message)
                <article class="thread-message {{ $message->sender_type === 'team' ? 'from-organization' : 'from-citizen' }}">
                    <div>
                        <strong>{{ $message->sender_type === 'team' ? ($message->user?->name ?? 'Kurum yetkilisi') : 'Vatandaş' }}</strong>
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
                    <span>Bu başvuruya yazılan kurum cevapları burada takip edilecek.</span>
                </div>
            @endforelse
        </section>
    </section>
@endsection
