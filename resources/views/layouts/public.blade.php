<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Şikayetçiyim Kıbrıs'))</title>
        <meta name="description" content="@yield('description', 'Kıbrıs genelindeki şikayetleri paylaşın, takip edin ve kurumların yanıtlarını inceleyin.')">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Şikayetçiyim Kıbrıs') }}">
        <meta property="og:title" content="@yield('title', config('app.name', 'Şikayetçiyim Kıbrıs'))">
        <meta property="og:description" content="@yield('description', 'Kıbrıs genelindeki şikayetleri paylaşın, takip edin ve kurumların yanıtlarını inceleyin.')">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="website">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800,900" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-body">
        <header class="site-header">
            <a class="brand" href="{{ url('/') }}" aria-label="Şikayetçiyim Kıbrıs ana sayfa">
                <span class="brand-symbol">✓</span>
                <span class="brand-text">Şikayetçiyim Kıbrıs</span>
            </a>

            <nav class="site-nav" aria-label="Ana menü">
                <a href="{{ route('reports.index') }}">Şikayetler</a>
                <a href="{{ route('municipalities.index') }}">Belediyeler</a>
                <a href="{{ route('reports.track-form') }}">Takip</a>
                <a href="{{ route('privacy') }}">Gizlilik</a>
            </nav>

            <div class="header-actions">
                @auth
                    @if (auth()->user()->hasRole('organization'))
                        <a class="login-link" href="{{ route('organization-portal.dashboard') }}">Kurum Paneli</a>
                    @elseif (! auth()->user()->hasAnyRole(['admin', 'editor', 'legal', 'moderator']))
                        <a class="login-link" href="{{ route('account.index') }}">Hesabım</a>
                    @endif
                    <form method="POST" action="{{ route('citizen.logout') }}">
                        @csrf
                        <button class="text-button" type="submit">Çıkış</button>
                    </form>
                @else
                    <a class="login-link" href="{{ route('citizen.login') }}">Giriş Yap</a>
                    <a class="login-link" href="{{ route('citizen.register') }}">Kayıt Ol</a>
                @endauth
                <a class="button button-primary nav-cta" href="{{ route('reports.create') }}">+ Şikayet Yaz</a>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site-footer">
            <span>Gizlilik öncelikli bildirim akışı</span>
            <span>Belgeler özel alanda saklanır</span>
            <span>Takip kodu ile sorgulanır</span>
            <a href="{{ route('privacy') }}">Güvenlik ve gizlilik</a>
        </footer>
    </body>
</html>
