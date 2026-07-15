<?php

use App\Http\Controllers\CorruptionReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CitizenAuthController;
use App\Http\Controllers\EvidenceFileController;
use App\Http\Controllers\FacebookAutomationCronController;
use App\Http\Controllers\OrganizationPortalController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CorruptionReportController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [CorruptionReportController::class, 'sitemap'])->name('sitemap');
Route::get('/facebook-automation/cron', FacebookAutomationCronController::class)
    ->middleware('throttle:6,1')
    ->name('facebook-automation.cron');
Route::view('/guvenlik-ve-gizlilik', 'pages.privacy')->name('privacy');
Route::redirect('/admin.php', '/admin');
Route::redirect('/login', '/giris')->name('login');

Route::middleware('guest')->group(function (): void {
    Route::get('/giris', [CitizenAuthController::class, 'loginForm'])->name('citizen.login');
    Route::post('/giris', [CitizenAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('citizen.login.store');

    Route::get('/kayit', [CitizenAuthController::class, 'registerForm'])->name('citizen.register');
    Route::post('/kayit', [CitizenAuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('citizen.register.store');

    Route::get('/sifremi-unuttum', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/sifre-sifirla', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::get('/kurum-davet/{token}', [OrganizationInvitationController::class, 'show'])
        ->name('organization-invitations.show');
    Route::post('/kurum-davet/{token}', [OrganizationInvitationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('organization-invitations.store');
});

Route::post('/cikis', [CitizenAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('citizen.logout');

Route::get('/hesabim', [AccountController::class, 'index'])
    ->middleware('auth')
    ->name('account.index');
Route::get('/hesabim/basvurular/{report:tracking_code}', [AccountController::class, 'show'])
    ->middleware('auth')
    ->name('account.reports.show');
Route::post('/hesabim/basvurular/{report:tracking_code}/mesaj', [AccountController::class, 'message'])
    ->middleware(['auth', 'throttle:8,1'])
    ->name('account.reports.message');

Route::prefix('kurum-paneli')
    ->middleware('auth')
    ->name('organization-portal.')
    ->group(function (): void {
        Route::get('/', [OrganizationPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/basvurular', [OrganizationPortalController::class, 'reports'])->name('reports.index');
        Route::get('/basvurular/{report:tracking_code}', [OrganizationPortalController::class, 'show'])->name('reports.show');
        Route::post('/basvurular/{report:tracking_code}/cevap', [OrganizationPortalController::class, 'respond'])
            ->middleware('throttle:8,1')
            ->name('reports.respond');
    });

Route::get('/admin/kanit-dosyalari/{evidenceFile}/indir', [EvidenceFileController::class, 'download'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('evidence-files.download');

Route::get('/sikayetler', [CorruptionReportController::class, 'index'])->name('reports.index');
Route::get('/belediyeler', [CorruptionReportController::class, 'municipalities'])->name('municipalities.index');
Route::get('/kurumlar/{entity}', [CorruptionReportController::class, 'entity'])->name('entities.show');
Route::get('/sikayetler/{report:tracking_code}', [CorruptionReportController::class, 'show'])->name('reports.show');
Route::post('/sikayetler/{report:tracking_code}/cozum', [CorruptionReportController::class, 'solutionFeedback'])
    ->middleware(['auth', 'throttle:8,1'])
    ->name('reports.solution-feedback');

Route::get('/ihbar', [CorruptionReportController::class, 'create'])->name('reports.create');
Route::post('/ihbar', [CorruptionReportController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('reports.store');
Route::get('/ihbar/gonderildi/{report}', [CorruptionReportController::class, 'submitted'])->name('reports.submitted');
Route::get('/ihbar/takip', [CorruptionReportController::class, 'trackForm'])->name('reports.track-form');
Route::post('/ihbar/takip', [CorruptionReportController::class, 'track'])
    ->middleware('throttle:20,1')
    ->name('reports.track');
