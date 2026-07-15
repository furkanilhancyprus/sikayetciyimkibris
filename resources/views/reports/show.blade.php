@extends('layouts.public')

@section('title', $report->title . ' - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="report-detail">
        <a class="back-link" href="{{ route('reports.index') }}">← Eski itirazlara dön</a>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <article class="detail-card">
            <div class="detail-kicker">
                <span>{{ $report->intake_type === 'complaint' ? 'İtiraz' : 'İhbar' }}</span>
                <time>{{ optional($report->published_at)->format('d.m.Y') }}</time>
                @if ($report->solution_status)
                    <span class="solution-badge {{ $report->solution_status === 'solved' ? 'is-solved' : 'is-unresolved' }}">
                        {{ $report->solution_status === 'solved' ? 'Çözüldü' : 'Çözülmedi' }}
                    </span>
                @endif
            </div>

            <h1>{{ $report->title }}</h1>

            <dl class="detail-meta">
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
                    <dd>
                        @if ($report->entity)
                            <a href="{{ route('entities.show', $report->entity) }}">{{ $report->entity->name }}</a>
                        @else
                            Belirtilmedi
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="detail-body">
                {!! nl2br(e($report->public_body)) !!}
            </div>
        </article>

        @if (! empty($timeline))
            <section class="report-timeline">
                <div class="section-heading">
                    <p class="eyebrow">Süreç</p>
                    <h2>Başvuru Zaman Çizelgesi</h2>
                </div>

                <div class="timeline-list">
                    @foreach ($timeline as $item)
                        <article class="timeline-item">
                            <span></span>
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <time>{{ optional($item['date'])->format('d.m.Y H:i') }}</time>
                                <p>{{ $item['detail'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @php
            $organizationResponses = $report->messages->where('sender_type', 'team')->sortByDesc('created_at');
        @endphp

        @if ($organizationResponses->isNotEmpty())
            <section class="official-responses">
                <div class="section-heading">
                    <p class="eyebrow">Kurum yanıtları</p>
                    <h2>Kurumun Cevabı</h2>
                </div>

                @foreach ($organizationResponses as $message)
                    <article class="official-response-card">
                        <div>
                            <strong>{{ $message->user?->name ?? $report->entity?->name ?? 'Kurum yetkilisi' }}</strong>
                            <time>{{ $message->created_at->format('d.m.Y H:i') }}</time>
                        </div>
                        <p>{!! nl2br(e($message->body)) !!}</p>
                    </article>
                @endforeach
            </section>
        @endif
        @if ($canGiveSolutionFeedback)
            <section class="solution-feedback-card">
                <div>
                    <p class="eyebrow">Çözüm geri bildirimi</p>
                    <h2>Bu kurum cevabı sorununu çözdü mü?</h2>
                    <p>Geri bildirimin kurum profilindeki çözüm oranına yansır.</p>
                </div>

                <form method="POST" action="{{ route('reports.solution-feedback', $report) }}">
                    @csrf
                    <div class="solution-actions">
                        <label>
                            <input type="radio" name="solution_status" value="solved" @checked(old('solution_status', $report->solution_status) === 'solved') required>
                            <span>Çözüldü</span>
                        </label>
                        <label>
                            <input type="radio" name="solution_status" value="unresolved" @checked(old('solution_status', $report->solution_status) === 'unresolved') required>
                            <span>Çözülmedi</span>
                        </label>
                    </div>

                    <label class="field">
                        Kısa not
                        <textarea name="solution_feedback" rows="4" maxlength="1200" placeholder="İstersen sonucu kısaca anlatabilirsin.">{{ old('solution_feedback', $report->solution_feedback) }}</textarea>
                        @error('solution_status')
                            <small>{{ $message }}</small>
                        @enderror
                        @error('solution_feedback')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <button class="button button-primary" type="submit">Geri Bildirimi Kaydet</button>
                </form>
            </section>
        @endif
    </section>
@endsection
