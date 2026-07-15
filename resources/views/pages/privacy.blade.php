@extends('layouts.public')

@section('title', 'Güvenlik ve Gizlilik - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact">
        <p class="eyebrow">Güvenlik ve gizlilik</p>
        <h1>Başvurunu kontrollü ve dikkatli şekilde ele alıyoruz.</h1>
        <p class="lead">Bu sayfa, vatandaşın başvuru yaparken nelere dikkat etmesi gerektiğini ve platformun hangi bilgileri nasıl ele aldığını açıklar.</p>
    </section>

    <section class="content-grid">
        <article>
            <h2>Kimlik bilgileri</h2>
            <p>İsminin yayınlanmasına açıkça izin vermediğin sürece kamuya açık sayfalarda kimlik bilgisi gösterilmez.</p>
        </article>
        <article>
            <h2>İletişim bilgisi</h2>
            <p>İletişim bilgisi opsiyoneldir. Eksik bilgi gerektiğinde sana ulaşmak için kullanılır; kamuya açık gösterilmez.</p>
        </article>
        <article>
            <h2>Kanıt dosyaları</h2>
            <p>Yüklenen dosyalar özel alanda saklanır. Kamuya açık metin ayrıca maskeleyerek hazırlanabilir.</p>
        </article>
        <article>
            <h2>Takip kodu</h2>
            <p>Başvuru takibi için verilen kodu saklamalısın. Kod kaybolursa güvenlik nedeniyle otomatik olarak tekrar gönderilmez.</p>
        </article>
    </section>
@endsection
