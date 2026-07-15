@extends('layouts.public')

@section('title', 'Kurum Hesabı - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact auth-hero">
        <p class="eyebrow">Kurum daveti</p>
        <h1>{{ $invitation->entity->name }} hesabını oluştur.</h1>
        <p class="lead">Bu bağlantı yalnızca davet edilen kurum/kuruluş yetkilisi içindir. Hesap açıldıktan sonra kurumuna gelen başvuruları panelden yanıtlayabilirsin.</p>
    </section>

    <section class="auth-shell">
        <form method="POST" action="{{ route('organization-invitations.store', $invitation->token) }}" class="auth-card">
            @csrf

            <label class="field">
                <span>Ad Soyad</span>
                <input name="name" value="{{ old('name', $invitation->contact_name) }}" required autocomplete="name">
                @error('name') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Kurum E-postası</span>
                <input type="email" name="email" value="{{ old('email', $invitation->invited_email) }}" required autocomplete="email">
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Şifre</span>
                <input type="password" name="password" required autocomplete="new-password">
                @error('password') <small>{{ $message }}</small> @enderror
            </label>

            <label class="field">
                <span>Şifre Tekrar</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </label>

            <button class="button button-primary" type="submit">Kurum Hesabını Oluştur</button>
        </form>
    </section>
@endsection
