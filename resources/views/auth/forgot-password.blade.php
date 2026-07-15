@extends('layouts.public')

@section('title', 'Şifremi Unuttum - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact auth-hero">
        <p class="eyebrow">Hesap güvenliği</p>
        <h1>Şifreni sıfırla.</h1>
        <p class="lead">E-posta adresini yaz; hesap varsa güvenli sıfırlama bağlantısı gönderilir.</p>
    </section>

    <section class="auth-shell">
        <form class="public-form auth-form" method="POST" action="{{ route('password.email') }}">
            @csrf

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <label class="field">
                <span>E-posta</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <div class="auth-actions">
                <button class="button button-primary" type="submit">Bağlantı Gönder</button>
                <a class="button button-secondary" href="{{ route('citizen.login') }}">Girişe Dön</a>
            </div>
        </form>
    </section>
@endsection
