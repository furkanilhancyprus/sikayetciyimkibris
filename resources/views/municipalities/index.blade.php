@extends('layouts.public')

@section('title', 'Belediyeler - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="municipality-hero">
        <div>
            <p class="eyebrow">Belediye hizmetleri</p>
            <h1>Belediyelere gelen itirazları tek ekranda takip et.</h1>
            <p class="lead">Yol, asfalt, çöp, su, aydınlatma, imar ve yerel hizmet başlıklarındaki yayınlanmış kayıtları belediye belediye inceleyebilirsin.</p>
        </div>
        <a class="button button-primary" href="{{ route('reports.create') }}">+ Yeni İtiraz Oluştur</a>
    </section>

    <section class="municipality-topics">
        @foreach ($municipalIssueAreas as $key => $label)
            <a href="{{ route('reports.index', ['issue_area' => $key]) }}">{{ $label }}</a>
        @endforeach
    </section>

    <section class="municipality-grid">
        @foreach ($municipalities as $municipality)
            <article class="municipality-card">
                <div class="municipality-card-top">
                    <span>{{ mb_substr($municipality->name, 0, 1) }}</span>
                    <div>
                        <strong>{{ $municipality->name }}</strong>
                        <small>{{ $municipality->published_reports_count }} yayınlanmış itiraz</small>
                    </div>
                </div>

                <div class="municipality-count">
                    <strong>{{ $municipality->published_reports_count }}</strong>
                    <span>kayıt</span>
                </div>

                <div class="municipality-actions">
                    <a class="button button-secondary" href="{{ route('entities.show', $municipality) }}">Profil</a>
                    <a class="button button-secondary" href="{{ route('reports.index', ['entity_id' => $municipality->id]) }}">İtirazları Gör</a>
                    <a class="button button-primary" href="{{ route('reports.create') }}">İtiraz Yaz</a>
                </div>
            </article>
        @endforeach
    </section>
@endsection
