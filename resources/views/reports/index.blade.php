@extends('layouts.public')

@section('title', 'Şikayetler - Şikayetçiyim Kıbrıs')

@php
    $activeFilters = collect($filters)->filter(fn ($value) => filled($value))->count();
    $popularIssues = array_slice($issueAreas, 0, 8);
@endphp

@section('content')
    <section class="complaints-hero">
        <div>
            <p class="eyebrow">İtirazlar</p>
            <h1>Yayındaki başvuruları ara, filtrele, gündemi takip et.</h1>
            <p class="lead">Belediye hizmetlerinden vatandaşlık süreçlerine, yol ve altyapı sorunlarından kurum mağduriyetlerine kadar yayınlanan kayıtlar burada toplanır.</p>
        </div>

        <a class="button button-primary" href="{{ route('reports.create') }}">+ Yeni İtiraz Oluştur</a>
    </section>

    @if (! $activeFilters)
        <section class="issue-directory">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Başlıklar</p>
                    <h2>Hangi konuda itirazlara bakmak istiyorsun?</h2>
                </div>
            </div>

            <div class="issue-directory-grid">
                @foreach ($popularIssues as $key => $label)
                    <a class="issue-directory-card" href="{{ route('reports.index', ['issue_area' => $key]) }}">
                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <strong>{{ $label }}</strong>
                        <small>{{ $issueDescriptions[$key] ?? 'Bu başlıktaki yayınlanmış itirazları görüntüle.' }}</small>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="complaints-explorer">
        <aside class="complaints-sidebar">
            <form class="filter-panel upgraded" method="GET" action="{{ route('reports.index') }}">
                <label class="field search-field">
                    <span>Arama</span>
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Başlık, kurum veya konu ara">
                </label>

                <label class="field">
                    <span>Tür</span>
                    <select name="intake_type">
                        <option value="">Tümü</option>
                        <option value="complaint" @selected(($filters['intake_type'] ?? '') === 'complaint')>İtiraz</option>
                        <option value="report" @selected(($filters['intake_type'] ?? '') === 'report')>İhbar</option>
                    </select>
                </label>

                <label class="field">
                    <span>Konu</span>
                    <select name="issue_area">
                        <option value="">Tüm konular</option>
                        @foreach ($issueAreas as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['issue_area'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Bölge</span>
                    <select name="region_id">
                        <option value="">Tüm bölgeler</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Kurum / Şirket</span>
                    <select name="entity_id">
                        <option value="">Tüm kurumlar</option>
                        @foreach ($entityGroups as $category => $categoryEntities)
                            <optgroup label="{{ $category }}">
                                @foreach ($categoryEntities as $entity)
                                    <option value="{{ $entity->id }}" @selected((string) ($filters['entity_id'] ?? '') === (string) $entity->id)>
                                        {{ $entity->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                <div class="filter-actions">
                    <button class="button button-primary" type="submit">Filtrele</button>
                    <a class="button button-secondary" href="{{ route('reports.index') }}">Temizle</a>
                </div>
            </form>

            <div class="topic-shortcuts">
                <strong>Çok aranan konular</strong>
                @foreach ($popularIssues as $key => $label)
                    <a href="{{ route('reports.index', ['issue_area' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </aside>

        <div class="complaints-results">
            <div class="results-toolbar">
                <div>
                    <strong>{{ $reports->total() }}</strong>
                    <span>yayınlanmış kayıt</span>
                    @if ($activeFilters)
                        <small>{{ $activeFilters }} filtre aktif</small>
                    @endif
                </div>
                <a href="{{ route('reports.create') }}">İtiraz Yaz</a>
            </div>

            <div class="complaints-card-list">
                @forelse ($reports as $report)
                    <article class="complaint-item upgraded">
                        <a class="complaint-avatar" href="{{ route('reports.show', $report) }}">
                            {{ mb_substr($report->title, 0, 1) }}
                        </a>

                        <div class="complaint-main">
                            <div class="complaint-kicker">
                                <span>{{ $report->intake_type === 'complaint' ? 'İtiraz' : 'İhbar' }}</span>
                                @if ($report->published_at)
                                    <time>{{ $report->published_at->format('d.m.Y') }}</time>
                                @endif
                            </div>

                            <h2>
                                <a href="{{ route('reports.show', $report) }}">{{ $report->title }}</a>
                            </h2>

                            <div class="complaint-meta">
                                <span>{{ $issueAreas[$report->issue_area] ?? 'Diğer' }}</span>
                                @if ($report->region)
                                    <span>{{ $report->region->name }}</span>
                                @endif
                                @if ($report->entity)
                                    <a href="{{ route('entities.show', $report->entity) }}">{{ $report->entity->name }}</a>
                                @endif
                            </div>
                        </div>

                        <a class="detail-link" href="{{ route('reports.show', $report) }}">İncele</a>
                    </article>
                @empty
                    <div class="empty-state upgraded">
                        <span class="result-icon">i</span>
                        <h2>Bu filtrelerle yayınlanmış itiraz yok.</h2>
                        <p>Filtreleri temizleyebilir veya yeni bir başvuru oluşturarak bu başlıktaki ilk kaydı açabilirsin.</p>
                        <div class="auth-actions">
                            <a class="button button-primary" href="{{ route('reports.create') }}">Yeni Başvuru Oluştur</a>
                            <a class="button button-secondary" href="{{ route('reports.index') }}">Filtreleri Temizle</a>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="pagination-shell">
                {{ $reports->links() }}
            </div>
        </div>
    </section>
@endsection
