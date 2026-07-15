@extends('layouts.public')

@section('title', 'Şikayetçiyim Kıbrıs')

@section('content')
    <section class="home-livebar">
        <p>Yayındaki şikayetleri, kurumları ve çözüm süreçlerini tek yerden takip et.</p>
        <a href="{{ route('reports.index') }}">Canlı Liste</a>
    </section>

    <section class="home-hero">
        <div class="home-copy">
            <p class="eyebrow">Kıbrıs için vatandaş sesi</p>
            <h1>Kıbrıs'taki şikayetleri ara, takip et, sesini duyur.</h1>
            <p class="lead">
                Belediyeler, kamu kurumları, yollar, vatandaşlık süreçleri ve şirketlerle yaşanan sorunlar burada görünür olur.
            </p>

            <form class="home-search" method="GET" action="{{ route('reports.index') }}">
                <label>
                    <span class="sr-only">İtiraz ara</span>
                    <input name="q" placeholder="Belediye, kurum, konu veya başlık ara">
                </label>
                <button type="submit">Ara</button>
            </form>

            <div class="home-quick-actions">
                <a class="button button-primary" href="{{ route('reports.create') }}">+ Yeni İtiraz Oluştur</a>
                <a class="button button-secondary" href="{{ route('reports.track-form') }}">Başvuru Takip</a>
            </div>
        </div>

        <div class="home-showcase" aria-label="Platform özeti">
            <div class="showcase-panel purple-panel">
                <span>Yayında</span>
                <strong>{{ number_format($stats['published_reports']) }}</strong>
                <small>itiraz</small>
            </div>
            <div class="showcase-panel green-panel">
                <span>Kapsam</span>
                <strong>{{ number_format($stats['regions']) }}</strong>
                <small>bölge</small>
            </div>
            <div class="showcase-card">
                <span class="avatar">Ş</span>
                <div>
                    <strong>Yol, asfalt, belediye hizmeti</strong>
                    <small>En hızlı gündeme gelen yerel sorunları filtrele.</small>
                </div>
            </div>
            <div class="showcase-card accent">
                <span class="avatar">K</span>
                <div>
                    <strong>{{ number_format($stats['entities']) }} kurum ve şirket</strong>
                    <small>Başvurunu doğru muhataba bağla.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="complaint-wall">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Gündemdeki itirazlar</p>
                <h2>Eski itirazlar ve son yayınlanan kayıtlar</h2>
            </div>
            <a class="button button-secondary" href="{{ route('reports.index') }}">Tümünü Gör</a>
        </div>

        <div class="complaint-card-grid">
            @forelse ($recentReports as $report)
                <a class="feed-card" href="{{ route('reports.show', $report) }}">
                    <div class="feed-card-top">
                        <span class="avatar">{{ mb_substr($report->reporter_name ?: $report->title, 0, 1) }}</span>
                        <div>
                            <strong>{{ $report->reporter_name ?: 'Vatandaş' }}</strong>
                            <small>{{ $report->intake_type === 'complaint' ? 'İtiraz' : 'İhbar' }}</small>
                        </div>
                    </div>
                    <h3>{{ $report->title }}</h3>
                    <p>
                        {{ $issueAreas[$report->issue_area] ?? 'Diğer' }}
                        @if ($report->region)
                            <span>• {{ $report->region->name }}</span>
                        @endif
                        @if ($report->entity)
                            <span>• {{ $report->entity->name }}</span>
                        @endif
                    </p>
                </a>
            @empty
                <article class="feed-card empty-feed">
                    <div class="feed-card-top">
                        <span class="avatar">1</span>
                        <div>
                            <strong>Henüz yayınlanan itiraz yok</strong>
                            <small>İlk kayıt burada öne çıkar.</small>
                        </div>
                    </div>
                    <h3>Yol, asfalt, vatandaşlık, belediye hizmeti veya kurum mağduriyetini görünür yap.</h3>
                    <p>Başvurular editör kontrolünden sonra eski itirazlar listesinde yayınlanır.</p>
                    <a class="button button-primary" href="{{ route('reports.create') }}">İlk İtirazı Oluştur</a>
                </article>

                @foreach (array_slice($issueAreas, 0, 5) as $key => $label)
                    <a class="feed-card topic-feed" href="{{ route('reports.index', ['issue_area' => $key]) }}">
                        <div class="feed-card-top">
                            <span class="avatar">{{ $loop->iteration + 1 }}</span>
                            <div>
                                <strong>{{ $label }}</strong>
                                <small>Konu başlığı</small>
                            </div>
                        </div>
                        <h3>{{ $label }} hakkında yayınlanan kayıtları görüntüle.</h3>
                        <p>Bu başlıktaki eski itirazlar ve yeni başvurular tek listede toplanır.</p>
                    </a>
                @endforeach
            @endforelse
        </div>
    </section>

    <section class="home-insights">
        <div class="trend-panel">
            <div class="section-heading compact">
                <div>
                    <p class="eyebrow">Trend belediyeler</p>
                    <h2>Bugün öne çıkan belediye başlıkları</h2>
                </div>
                <a href="{{ route('municipalities.index') }}">Belediyeler</a>
            </div>

            <div class="trend-rank-list">
                @foreach ($trendingMunicipalities as $municipality)
                    <a class="trend-rank-row" href="{{ route('reports.index', ['entity_id' => $municipality->id]) }}">
                        <span>{{ $loop->iteration }}</span>
                        <div>
                            <strong>{{ $municipality->name }}</strong>
                            <small>Belediye hizmetleri ve yerel sorunlar</small>
                        </div>
                        <em>{{ $municipality->published_reports_count }}</em>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="talked-panel">
            <div class="section-heading compact">
                <div>
                    <p class="eyebrow">Çok konuşulanlar</p>
                    <h2>Yanıt ve takip alan itirazlar</h2>
                </div>
                <a href="{{ route('reports.index') }}">Liste</a>
            </div>

            <div class="talked-list">
                @forelse ($talkedReports as $report)
                    <a class="talked-row" href="{{ route('reports.show', $report) }}">
                        <span class="avatar">{{ mb_substr($report->title, 0, 1) }}</span>
                        <div>
                            <strong>{{ $report->title }}</strong>
                            <small>
                                {{ $report->entity?->name ?? $issueAreas[$report->issue_area] ?? 'İtiraz' }}
                                · {{ $report->messages_count }} yorum
                            </small>
                        </div>
                    </a>
                @empty
                    <div class="empty-feed compact">
                        <strong>Henüz konuşulan itiraz yok.</strong>
                        <span>Kurum yanıtları ve ek açıklamalar geldikçe bu alan canlanacak.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="topic-band">
        <div>
            <p class="eyebrow">Çok aranan konular</p>
            <h2>Vatandaşın en çok zorlandığı alanlar</h2>
        </div>
        <div class="issue-tags">
            @foreach (array_slice($issueAreas, 0, 12) as $key => $label)
                <a href="{{ route('reports.index', ['issue_area' => $key]) }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>
@endsection
