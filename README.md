# Şikayetçiyim Kıbrıs

Resmî alan adı: [sikayetciyimkibris.com](https://sikayetciyimkibris.com)

Laravel tabanlı vatandaş itirazı, ihbar ve kamuya açık yayın/moderasyon platformu.

## Stack

- Laravel `12.62.0`
- Filament `3.3.54`
- Spatie Laravel Permission `6.25.0`
- SQLite local/test, MySQL/MariaDB production hedefi
- Blade, Tailwind, Vite
- Database queue driver

## Public Özellikler

- Adım adım itiraz/ihbar başvuru formu.
- Konu, bölge, kurum/şirket seçimi.
- Bölge seçimine göre kurum filtreleme.
- Kanıt dosyası yükleme ve seçilen dosyaları gösterme.
- Takip kodu üretimi, kopyalama ve takip ekranı.
- Eski/yayınlanmış itirazları arama ve filtreleme.
- Yayınlanmış kayıt detay sayfası.
- Güvenlik ve gizlilik bilgilendirme sayfası.

## Admin Özellikler

- Filament admin paneli: `/admin`
- Rol tabanlı erişim: `moderator`, `editor`, `legal`, `admin`
- Başvuru türü, konu, bölge, kurum ve durum filtreleri.
- Editör/hukuk onayı olmadan yayınlamayı engelleyen model koruması.
- Kamuya açık metin için ayrı `public_body` alanı.

## Facebook Reklam Otomasyonu

Admin panelde `Reklam Otomasyonu` grubu altında üç ekran bulunur:

- `Facebook Reklamları`: yorum metni, link, görsel URL ve aktif/pasif reklam havuzu.
- `Facebook Ayarları`: Haberler KKTC sayfası, frekans limitleri, gecikme aralığı ve onaylı çalışma modu.
- `Facebook Yorum Logları`: hangi post için hangi reklamın planlandığı/gönderildiği/hata aldığı.

Otomasyon varsayılan olarak kapalı ve onay gerektirir. Meta Graph API tokenları bağlanmadan canlı yorum göndermez.

Gerekli Meta bilgileri:

- Haberler KKTC Facebook Page ID
- Meta Developer App ID ve App Secret
- Uzun ömürlü Page Access Token
- Sayfa postlarını okuma ve sayfa adına yorum/etkileşim yönetme izinleri
- Webhook kullanacaksak webhook callback URL ve verify token

Manuel test komutu:

```bash
php artisan facebook:queue-comment FACEBOOK_POST_ID
```

## Local Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

## Admin Kullanıcı

`.env` içinde şu alanları doldurup seed çalıştır:

```env
ADMIN_NAME="Şikayetçiyim Kıbrıs Admin"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=strong-password
```

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

Yerelde bu oturumda oluşturulan kullanıcı:

- Email: `admin@sikayetciyimkibris.test`
- Şifre: `Admin12345!`

Canlıya çıkmadan bu şifre mutlaka değiştirilmelidir.

## Doğrulama

```bash
php artisan test
composer audit
npm audit --audit-level=low
npm run build
```

## Güvenlik Notları

- Public ve admin yanıtlarında güvenlik başlıkları uygulanır: CSP, frame engeli, MIME sniffing engeli, referrer ve permission policy.
- Oturum çerezleri HTTP only, same-site strict ve şifreli session ayarlarıyla çalışacak şekilde yapılandırılmıştır.
- Kurum hesapları public kayıttan açılamaz; yalnızca adminin oluşturduğu süreli özel davet linkiyle açılır.
- Kurum kullanıcıları yalnızca bağlı oldukları kurum/kuruluşa ait başvuruları görebilir ve yalnızca o kayıtlara cevap yazabilir.
- Kanıt dosyaları public disk yerine private diske kaydedilir; dosya yolu şifrelenir, orijinal dosya adı temizlenerek saklanır.
- Başvuru konuları allowlist ile doğrulanır, dosya türü/uzantısı/MIME tipi ve dosya boyutu sınırlandırılır.

## Canlıya Çıkış Kontrol Listesi

- `APP_ENV=production`, `APP_DEBUG=false`
- Güçlü `APP_KEY`
- MySQL/MariaDB bağlantısı
- `ADMIN_EMAIL` ve güçlü `ADMIN_PASSWORD`
- `FILESYSTEM_DISK=private`
- Queue/cron ayarı
- Günlük yedekleme
- Dosya yükleme limitleri ve PHP `upload_max_filesize`
- Mail ayarları
- SSL/HTTPS
- Moderasyon rolleri ve gerçek admin kullanıcıları

## Shared Hosting Deploy Notları

Önerilen yapı:

- Uygulama kökü public olmayan bir dizinde dursun: ör. `~/apps/sikayetciyimkibris`
- Domain document root `public` klasörünü göstersin: ör. `~/apps/sikayetciyimkibris/public`
- Eğer hosting paneli document root değiştirmiyorsa `public` içeriğini `public_html` altına taşımak yerine önce panelden kök dizin ayarı veya symlink imkanı kontrol edilmeli.

İlk kurulum:

```bash
git clone <repo-url> sikayetciyimkibris
cd sikayetciyimkibris
cp .env.production.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=PlatformReferenceSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Her deploy:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Sunucuda Node yoksa `npm run build` localde çalıştırılıp `public/build` repoya dahil edilmelidir. Node varsa deploy sırasında `npm ci && npm run build` çalıştırılabilir.
