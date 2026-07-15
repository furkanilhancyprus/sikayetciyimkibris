@extends('layouts.public')

@section('title', 'Giriş Yap - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact auth-hero">
        <p class="eyebrow">Vatandaş hesabı</p>
        <h1>Giriş yap.</h1>
        <p class="lead">Hesabınla başvurularını daha kolay takip edebilir, yeni itiraz oluştururken bilgilerini tekrar yazmadan ilerleyebilirsin.</p>
    </section>

    <section class="auth-shell">
        <form class="public-form auth-form" method="POST" action="{{ route('citizen.login.store') }}">
            @csrf

            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Giriş yapılamadı.</strong>
                    <span>Lütfen bilgilerini kontrol et.</span>
                </div>
            @endif

            <label class="field">
                <span>E-posta</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Şifre</span>
                <input type="password" name="password" required autocomplete="current-password">
                @error('password') <small>{{ $message }}</small> @enderror
            </label>

            <label class="check-field">
                <input type="checkbox" name="remember" value="1">
                <span>Beni hatırla</span>
            </label>

            <div class="auth-actions">
                <button class="button button-primary" type="submit">Giriş Yap</button>
                <a class="button button-secondary" href="{{ route('citizen.register') }}">Kayıt Ol</a>
            </div>
            <a class="auth-muted-link" href="{{ route('password.request') }}">Şifremi unuttum</a>
        </form>
    </section>
@endsection
