@extends('layouts.public')

@section('title', 'Kayıt Ol - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact auth-hero">
        <p class="eyebrow">Vatandaş hesabı</p>
        <h1>Kayıt ol.</h1>
        <p class="lead">Kayıt olmak opsiyoneldir. İstersen hesap açmadan da itiraz veya ihbar gönderebilirsin.</p>
    </section>

    <section class="auth-shell">
        <form class="public-form auth-form" method="POST" action="{{ route('citizen.register.store') }}">
            @csrf

            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Kayıt tamamlanamadı.</strong>
                    <span>Lütfen işaretlenen alanları kontrol et.</span>
                </div>
            @endif

            <label class="field">
                <span>Ad Soyad</span>
                <input name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>E-posta</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Telefon <em>opsiyonel</em></span>
                <input name="phone" value="{{ old('phone') }}" autocomplete="tel">
                @error('phone') <small>{{ $message }}</small> @enderror
            </label>

            <div class="split-fields">
                <label class="field">
                    <span>Şifre</span>
                    <input type="password" name="password" required autocomplete="new-password">
                    @error('password') <small>{{ $message }}</small> @enderror
                </label>

                <label class="field">
                    <span>Şifre Tekrar</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
            </div>

            <div class="auth-actions">
                <button class="button button-primary" type="submit">Kayıt Ol</button>
                <a class="button button-secondary" href="{{ route('reports.create') }}">Kayıtsız Devam Et</a>
            </div>
        </form>
    </section>
@endsection
