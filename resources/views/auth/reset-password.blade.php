@extends('layouts.public')

@section('title', 'Şifre Sıfırla - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact auth-hero">
        <p class="eyebrow">Yeni şifre</p>
        <h1>Hesabına yeniden eriş.</h1>
        <p class="lead">Yeni şifren en az 10 karakter, harf ve rakam içermelidir.</p>
    </section>

    <section class="auth-shell">
        <form class="public-form auth-form" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label class="field">
                <span>E-posta</span>
                <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Yeni Şifre</span>
                <input type="password" name="password" required autocomplete="new-password">
                @error('password') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Yeni Şifre Tekrar</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </label>

            <button class="button button-primary" type="submit">Şifreyi Güncelle</button>
        </form>
    </section>
@endsection
