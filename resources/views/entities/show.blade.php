@extends('layouts.public')

@section('title', $entity->name.' - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="entity-profile">
        <div class="entity-hero">
            <div>
                <p class="eyebrow">{{ $entity->category }}</p>
                <h1>{{ $entity->name }}</h1>
                <p class="lead">
                    {{ $entity->region?->name ? $entity->region->name.' bölgesindeki' : 'Bu kuruma ait' }}
                    yayınlanmış itirazları, cevap durumunu ve çözüm geri bildirimlerini tek ekranda incele.
                </p>
            </div>
            <a class="button button-primary" href="{{ route('reports.create') }}">+ Yeni İtiraz Oluştur</a>
        </div>

        <div class="entity-stats">
            <article>
                <strong>{{ $stats['total'] }}</strong>
                <span>yayınlanmış itiraz</span>
            </article>
            <article>
                <strong>%{{ $stats['response_rate'] }}</strong>
                <span>cevap oranı</span>
            </article>
            <article>
                <strong>%{{ $stats['solution_rate'] }}</strong>
                <span>çözüm memnuniyeti</span>
            </article>
            <article>
                <strong>{{ $stats['unresolved'] }}</strong>
                <span>çözülmedi işareti</span>
            </article>
        </div>

        <div class="entity-profile-grid">
            <section class="entity-panel">
                <div class="section-heading compact">
                    <div>
                        <p class="eyebrow">Konu dağılımı</p>
                        <h2>En çok hangi başlıklarda itiraz var?</h2>
                    </div>
                </div>

                <div class="entity-topic-list">
                    @forelse ($issueCounts as $item)
                        <a href="{{ route('reports.index', ['entity_id' => $entity->id, 'issue_area' => $item->issue_area]) }}">
                            <span>{{ $issueAreas[$item->issue_area] ?? 'Diğer' }}</span>
                            <strong>{{ $item->total }}</strong>
                        </a>
                    @empty
                        <div class="empty-feed compact">
                            <strong>Henüz yayınlanmış itiraz yok.</strong>
                            <span>Bu kurum için yayınlanan kayıtlar burada görünecek.</span>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="entity-panel">
                <div class="section-heading compact">
                    <div>
                        <p class="eyebrow">Son kayıtlar</p>
                        <h2>Bu kuruma ait son itirazlar</h2>
                    </div>
                    <a href="{{ route('reports.index', ['entity_id' => $entity->id]) }}">Tümü</a>
                </div>

                <div class="entity-report-list">
                    @forelse ($recentReports as $report)
                        <a class="entity-report-row" href="{{ route('reports.show', $report) }}">
                            <div>
                                <strong>{{ $report->title }}</strong>
                                <small>
                                    {{ $issueAreas[$report->issue_area] ?? 'Diğer' }}
                                    @if ($report->region)
                                        · {{ $report->region->name }}
                                    @endif
                                </small>
                            </div>
                            <span class="{{ $report->solution_status === 'solved' ? 'is-solved' : ($report->solution_status === 'unresolved' ? 'is-unresolved' : '') }}">
                                @if ($report->solution_status === 'solved')
                                    Çözüldü
                                @elseif ($report->solution_status === 'unresolved')
                                    Çözülmedi
                                @elseif ($report->approved_responses_count > 0)
                                    Cevaplandı
                                @else
                                    Yanıt bekliyor
                                @endif
                            </span>
                        </a>
                    @empty
                        <div class="empty-feed compact">
                            <strong>Henüz kayıt yok.</strong>
                            <span>Bu kurumla ilgili yayınlanmış itirazlar burada listelenecek.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
