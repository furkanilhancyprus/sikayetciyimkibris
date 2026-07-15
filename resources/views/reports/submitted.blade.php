@extends('layouts.public')

@section('title', 'Başvuru Alındı - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="result-page">
        <div class="result-card">
            <span class="result-icon">✓</span>
            <p class="eyebrow">Başvuru alındı</p>
            <h1>Takip kodunu güvenli bir yerde sakla.</h1>
            <p>Bu kod e-posta veya SMS ile gönderilmez. Başvurunun durumunu sadece bu kodla sorgulayabilirsin.</p>

            <div class="tracking-box">
                <strong class="tracking-code" data-tracking-code>{{ $trackingCode }}</strong>
                <button class="button button-primary" type="button" data-copy-code>Kodu Kopyala</button>
                <button class="button button-secondary" type="button" onclick="window.print()">Yazdır</button>
            </div>

            <p class="copy-feedback" data-copy-feedback hidden>Kod panoya kopyalandı.</p>

            <div class="result-actions">
                <a class="button button-secondary" href="{{ route('reports.track-form') }}">Takip Ekranına Git</a>
                <a class="button button-secondary" href="{{ route('reports.create') }}">Yeni Başvuru Oluştur</a>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const button = document.querySelector('[data-copy-code]');
            const code = document.querySelector('[data-tracking-code]');
            const feedback = document.querySelector('[data-copy-feedback]');

            button?.addEventListener('click', async () => {
                await navigator.clipboard.writeText(code.textContent.trim());
                feedback.hidden = false;
            });
        })();
    </script>
@endsection
