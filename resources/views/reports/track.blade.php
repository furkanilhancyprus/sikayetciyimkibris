@extends('layouts.public')

@section('title', 'Başvuru Takip - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact">
        <p class="eyebrow">Başvuru takip</p>
        <h1>Takip kodunla dosya durumunu kontrol et.</h1>
        <p class="lead">Kod yalnızca başvuru sonrasında ekranda gösterilir. Kaybettiysen güvenlik nedeniyle tekrar gönderilmez.</p>
    </section>

    <section class="track-shell">
        <form class="public-form track-form" method="POST" action="{{ route('reports.track') }}">
            @csrf

            <label class="field">
                <span>Takip Kodu</span>
                <input name="tracking_code" maxlength="12" required placeholder="ORNEK12345" autocomplete="off">
                @error('tracking_code') <small>{{ $message }}</small> @enderror
            </label>

            <button class="button button-primary form-submit" type="submit">Durumu Kontrol Et</button>
        </form>
    </section>
@endsection
