<?php

namespace Tests\Feature;

use App\Enums\CorruptionReportStatus;
use App\Exceptions\InvalidReportTransition;
use App\Filament\Resources\CorruptionReportResource;
use App\Models\CorruptionReport;
use App\Models\Entity;
use App\Models\ModerationLog;
use App\Models\OrganizationInvitation;
use App\Models\Region;
use App\Models\User;
use App\Presenters\PublicReportPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CorruptionReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['verified_user', 'reporter', 'organization', 'editor', 'legal', 'admin'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_public_header_hides_admin_entry_and_admin_php_redirects_to_panel(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Yetkili Girişi')
            ->assertSee('Kayıt Ol');

        $this->get('/admin.php')
            ->assertRedirect('/admin');
    }

    public function test_public_responses_include_security_headers(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    public function test_citizen_can_register_without_admin_panel_access(): void
    {
        $response = $this->post(route('citizen.register.store'), [
            'name' => 'Vatandaş Kullanıcı',
            'email' => 'vatandas@example.com',
            'phone' => '05330000000',
            'password' => 'Sifre12345',
            'password_confirmation' => 'Sifre12345',
        ]);

        $response->assertRedirect(route('home'));

        $user = User::query()->where('email_hash', hash('sha256', 'vatandas@example.com'))->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('reporter'));
        $this->assertFalse($user->hasAnyRole(['moderator', 'editor', 'legal', 'admin']));
    }

    public function test_password_reset_link_request_uses_password_broker_without_revealing_accounts(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => 'reset@example.com'])
            ->assertSessionHas('status');

        $this->assertNotSame(Password::INVALID_USER, Password::sendResetLink(['email' => 'reset@example.com']));
    }

    public function test_admin_dashboard_shows_management_widgets_and_report_navigation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Şikayetçiyim Kıbrıs')
            ->assertSee('Toplam Başvuru')
            ->assertSee('Son Gelen İtiraz ve İhbarlar')
            ->assertSee('İtiraz ve İhbarlar');
    }

    public function test_organization_invitation_creates_entity_bound_panel_user(): void
    {
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye', 'region_id' => $region->id]);
        $invitation = OrganizationInvitation::query()->create([
            'entity_id' => $entity->id,
            'invited_email' => 'kurum@example.com',
            'contact_name' => 'Kurum Yetkilisi',
        ]);

        $this->get(route('organization-invitations.show', $invitation->token))
            ->assertOk()
            ->assertSee('Girne Belediyesi');

        $response = $this->post(route('organization-invitations.store', $invitation->token), [
            'name' => 'Kurum Yetkilisi',
            'email' => 'kurum@example.com',
            'password' => 'Kurum12345',
            'password_confirmation' => 'Kurum12345',
        ]);

        $response->assertRedirect(route('organization-portal.dashboard'));

        $user = User::query()->where('email_hash', hash('sha256', 'kurum@example.com'))->firstOrFail();
        $this->assertSame($entity->id, $user->entity_id);
        $this->assertTrue($user->hasRole('organization'));
        $this->assertNotNull($invitation->refresh()->accepted_at);

        $this->post(route('citizen.logout'));

        $this->post(route('organization-invitations.store', $invitation->token), [
            'name' => 'Başka Yetkili',
            'email' => 'kurum@example.com',
            'password' => 'Kurum12345',
            'password_confirmation' => 'Kurum12345',
        ])->assertStatus(410);
    }

    public function test_expired_organization_invitation_cannot_be_used(): void
    {
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $invitation = OrganizationInvitation::query()->create([
            'entity_id' => $entity->id,
            'invited_email' => 'expired@example.com',
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('organization-invitations.show', $invitation->token))
            ->assertStatus(410);
    }

    public function test_organization_user_only_sees_own_entity_reports_and_can_respond_to_them(): void
    {
        $girne = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $lefkosia = Entity::query()->create(['name' => 'Lefkoşa Türk Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['entity_id' => $girne->id]);
        $organizationUser->assignRole('organization');

        $ownReport = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $girne->id,
            'title' => 'Girne asfalt sorunu',
            'body' => 'Girne Belediyesi için gelen başvuru.',
        ]);

        $otherReport = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $lefkosia->id,
            'title' => 'Lefkoşa kaldırım sorunu',
            'body' => 'Başka kuruma ait başvuru.',
        ]);

        $this->actingAs($organizationUser)
            ->get('/admin/corruption-reports')
            ->assertOk()
            ->assertSee('Girne asfalt sorunu')
            ->assertDontSee('Lefkoşa kaldırım sorunu');

        $this->assertTrue(CorruptionReportResource::canOrganizationRespond($ownReport));
        $this->assertFalse(CorruptionReportResource::canOrganizationRespond($otherReport));
    }

    public function test_organization_user_has_dedicated_portal_and_can_submit_pending_response(): void
    {
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['entity_id' => $entity->id]);
        $organizationUser->assignRole('organization');

        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $entity->id,
            'title' => 'Kurum panelinde görünen yol şikayeti',
            'body' => 'Kurum panelinde test edilmek için oluşturulan yeterli uzunlukta başvuru metni.',
        ]);

        $this->actingAs($organizationUser)
            ->get(route('organization-portal.dashboard'))
            ->assertOk()
            ->assertSee('Girne Belediyesi')
            ->assertSee('Kurum panelinde görünen yol şikayeti');

        $this->actingAs($organizationUser)
            ->post(route('organization-portal.reports.respond', $report), [
                'body' => 'Kurum ekipleri sahada inceleme başlatmıştır ve sonuç ayrıca paylaşılacaktır.',
            ])
            ->assertRedirect(route('organization-portal.reports.show', $report));

        $this->assertDatabaseHas('report_messages', [
            'corruption_report_id' => $report->id,
            'sender_type' => 'team',
            'status' => 'pending',
        ]);
    }

    public function test_public_report_detail_shows_organization_responses(): void
    {
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['name' => 'Girne Belediyesi Yetkilisi', 'entity_id' => $entity->id]);
        $organizationUser->assignRole('organization');

        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $entity->id,
            'title' => 'Girne yolunda çukur problemi',
            'body' => 'Girne ana yolunda uzun süredir giderilmeyen çukurlar araçlara zarar veriyor.',
            'public_body' => 'Girne ana yolundaki çukurlar araçlara zarar veriyor.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        $report->messages()->create([
            'sender_type' => 'team',
            'user_id' => $organizationUser->id,
            'body' => 'Ekiplerimiz yol bakım programına aldı.',
            'status' => 'approved',
        ]);

        $this->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Başvuru Zaman Çizelgesi')
            ->assertSee('Kurumun Cevabı')
            ->assertSee('Girne Belediyesi Yetkilisi')
            ->assertSee('Ekiplerimiz yol bakım programına aldı.');
    }

    public function test_entity_profile_shows_response_and_solution_metrics(): void
    {
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['entity_id' => $entity->id]);
        $organizationUser->assignRole('organization');

        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $entity->id,
            'title' => 'Girne yolunda çukur problemi',
            'body' => 'Girne ana yolunda uzun süredir giderilmeyen çukurlar araçlara zarar veriyor.',
            'public_body' => 'Girne ana yolundaki çukurlar araçlara zarar veriyor.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
            'solution_status' => 'solved',
            'solution_feedback_at' => now(),
        ]);

        $report->messages()->create([
            'sender_type' => 'team',
            'user_id' => $organizationUser->id,
            'body' => 'Yol bakım programına alındı.',
            'status' => 'approved',
        ]);

        $this->get(route('entities.show', $entity))
            ->assertOk()
            ->assertSee('Girne Belediyesi')
            ->assertSee('cevap oranı')
            ->assertSee('çözüm memnuniyeti')
            ->assertSee('Girne yolunda çukur problemi')
            ->assertSee('Çözüldü');
    }

    public function test_assigned_citizen_can_mark_report_solution_after_approved_response(): void
    {
        $citizen = User::factory()->create();
        $citizen->assignRole('reporter');
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['entity_id' => $entity->id]);
        $organizationUser->assignRole('organization');

        $report = CorruptionReport::query()->create([
            'assigned_reporter_id' => $citizen->id,
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $entity->id,
            'title' => 'Çözüm geri bildirimi verilecek başvuru',
            'body' => 'Vatandaş çözüm geri bildirimi için yeterli uzunlukta başvuru metni.',
            'public_body' => 'Çözüm geri bildirimi için yayınlanmış güvenli başvuru metni.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        $report->messages()->create([
            'sender_type' => 'team',
            'user_id' => $organizationUser->id,
            'body' => 'Sorun giderildi.',
            'status' => 'approved',
        ]);

        $this->actingAs($citizen)
            ->post(route('reports.solution-feedback', $report), [
                'solution_status' => 'solved',
                'solution_feedback' => 'Sorun giderildi, teşekkür ederim.',
            ])
            ->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('corruption_reports', [
            'id' => $report->id,
            'solution_status' => 'solved',
            'solution_feedback_by' => $citizen->id,
        ]);

        $this->actingAs($citizen)
            ->get(route('reports.show', $report->refresh()))
            ->assertOk()
            ->assertSee('Çözüldü');
    }

    public function test_pending_organization_response_is_hidden_until_approved(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $legal = User::factory()->create();
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye']);
        $organizationUser = User::factory()->create(['entity_id' => $entity->id]);
        $organizationUser->assignRole('organization');

        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'entity_id' => $entity->id,
            'title' => 'Girne yolunda çukur problemi',
            'body' => 'Girne ana yolunda uzun süredir giderilmeyen çukurlar araçlara zarar veriyor.',
            'public_body' => 'Girne ana yolundaki çukurlar araçlara zarar veriyor.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        $message = $report->messages()->create([
            'sender_type' => 'team',
            'user_id' => $organizationUser->id,
            'body' => 'Bu cevap önce moderasyona düşmelidir.',
            'status' => 'pending',
        ]);

        $this->get(route('reports.show', $report))
            ->assertOk()
            ->assertDontSee('Bu cevap önce moderasyona düşmelidir.');

        $message->approve($editor, 'Cevap uygun bulundu.');

        $this->get(route('reports.show', $report->refresh()))
            ->assertOk()
            ->assertSee('Bu cevap önce moderasyona düşmelidir.');

        $this->assertDatabaseHas('moderation_logs', [
            'loggable_type' => \App\Models\ReportMessage::class,
            'loggable_id' => $message->id,
            'action' => 'organization_response_approved',
        ]);
    }

    public function test_report_cannot_be_published_by_direct_assignment_without_approvals(): void
    {
        $report = CorruptionReport::query()->create([
            'title' => 'Municipal tender payment concern',
            'body' => 'A detailed allegation with enough substance to satisfy the encrypted field validation.',
        ]);

        $this->expectException(InvalidReportTransition::class);

        $report->forceFill(['status' => CorruptionReportStatus::Published])->save();
    }

    public function test_report_requires_editor_and_legal_approval_before_publication(): void
    {
        $reporter = User::factory()->create();
        $editor = User::factory()->create();
        $legal = User::factory()->create();

        $reporter->assignRole('reporter');
        $editor->assignRole('editor');
        $legal->assignRole('legal');

        $report = CorruptionReport::query()->create([
            'title' => 'Public procurement allegation',
            'body' => 'A detailed allegation with enough substance to satisfy the encrypted field validation.',
            'public_body' => 'A reviewed public version of the procurement allegation.',
        ]);

        $report->startReview($reporter, 'Initial triage started.');
        $report->refresh()->approveByEditor($editor, 'Documents match the public tender record.');

        try {
            $report->refresh()->publish($editor, 'Attempting to publish before legal approval.');
            $this->fail('Report was published without legal approval.');
        } catch (InvalidReportTransition) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseMissing('corruption_reports', [
            'id' => $report->id,
            'status' => CorruptionReportStatus::Published->value,
        ]);

        $report->refresh()->approveByLegal($legal, 'Legal review completed.');
        $report->refresh()->publish($editor, 'Both approvals are now recorded.');

        $this->assertSame(CorruptionReportStatus::Published, $report->refresh()->status);
        $this->assertNotNull($report->published_at);
        $this->assertSame(4, ModerationLog::query()
            ->where('loggable_type', CorruptionReport::class)
            ->where('loggable_id', $report->id)
            ->count());
    }

    public function test_report_requires_redacted_public_body_before_publication(): void
    {
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $editor->assignRole('editor');
        $legal->assignRole('legal');

        $report = CorruptionReport::query()->create([
            'title' => 'Kimlik bilgisi içeren başvuru',
            'body' => 'Bu özgün metin kamuya açılmaması gereken kişisel bilgiler içeren ayrıntılı başvurudur.',
        ]);

        $report->startReview($editor, 'İnceleme başlatıldı.');
        $report->refresh()->approveByEditor($editor, 'Editör incelemesi tamamlandı.');
        $report->refresh()->approveByLegal($legal, 'Hukuk incelemesi tamamlandı.');

        $this->expectException(InvalidReportTransition::class);
        $report->refresh()->publish($editor, 'Yayınlama denemesi.');
    }

    public function test_public_presenter_hides_reporter_name_without_explicit_consent(): void
    {
        $report = CorruptionReport::query()->create([
            'title' => 'Conflict of interest allegation',
            'body' => 'A detailed allegation with enough substance to satisfy the encrypted field validation.',
            'reporter_name' => 'Private Source',
            'identity_disclosed' => false,
        ]);

        $this->assertNull((new PublicReportPresenter($report))->reporterName());

        $report->forceFill([
            'identity_disclosed' => true,
            'disclosure_consent_at' => now(),
        ])->save();

        $this->assertSame('Private Source', (new PublicReportPresenter($report->refresh()))->reporterName());
    }

    public function test_public_wizard_submission_creates_trackable_report(): void
    {
        $region = Region::query()->create(['name' => 'Lefkoşa', 'type' => 'il']);
        $entity = Entity::query()->create(['name' => 'Lefkoşa Türk Belediyesi', 'category' => 'belediye', 'region_id' => $region->id]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'entity_id' => $entity->id,
            'title' => 'Mahalle yolunda asfalt sorunu',
            'body' => 'Mahalle yolunda uzun süredir devam eden ciddi asfalt ve kaldırım sorunu vardır. Araçlar ve yayalar için risk oluşturuyor.',
            'reporter_contact' => 'vatandas@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $report = CorruptionReport::query()->firstOrFail();

        $response->assertRedirect(route('reports.submitted', $report));
        $this->assertSame('complaint', $report->intake_type);
        $this->assertSame('roads_asphalt', $report->issue_area);
        $this->assertSame($region->id, $report->region_id);
        $this->assertSame($entity->id, $report->entity_id);
    }

    public function test_authenticated_citizen_can_see_their_reports_on_account_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('reporter');

        CorruptionReport::query()->create([
            'assigned_reporter_id' => $user->id,
            'intake_type' => 'complaint',
            'issue_area' => 'municipal_services',
            'title' => 'Hesabım ekranında görünen başvuru',
            'body' => 'Hesaba bağlı başvuru için yeterli uzunlukta açıklama metni.',
        ]);

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('Hesabım ekranında görünen başvuru');
    }

    public function test_authenticated_citizen_can_open_report_and_submit_followup_message(): void
    {
        $user = User::factory()->create();
        $user->assignRole('reporter');

        $report = CorruptionReport::query()->create([
            'assigned_reporter_id' => $user->id,
            'intake_type' => 'complaint',
            'issue_area' => 'municipal_services',
            'title' => 'Vatandaş hesabı detay başvurusu',
            'body' => 'Vatandaş hesabı detay ekranında görünen yeterli uzunlukta başvuru metni.',
        ]);

        $this->actingAs($user)
            ->get(route('account.reports.show', $report))
            ->assertOk()
            ->assertSee('Vatandaş hesabı detay başvurusu')
            ->assertSee('Ek Açıklama Gönder');

        $this->actingAs($user)
            ->post(route('account.reports.message', $report), [
                'body' => 'Başvuruya ek olarak paylaşmak istediğim yeni ve yeterince uzun açıklama metni.',
            ])
            ->assertRedirect(route('account.reports.show', $report));

        $this->assertDatabaseHas('report_messages', [
            'corruption_report_id' => $report->id,
            'sender_type' => 'reporter',
            'status' => 'pending',
        ]);
    }

    public function test_public_submission_rejects_unknown_issue_area(): void
    {
        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'unknown_area',
            'title' => 'Geçersiz konu bildirimi',
            'body' => 'Bu metin minimum uzunluk şartını geçmek için yeterince uzun yazılmış örnek bir içeriktir.',
        ]);

        $response->assertSessionHasErrors('issue_area');
        $this->assertDatabaseCount('corruption_reports', 0);
    }

    public function test_validation_errors_are_shown_in_turkish(): void
    {
        $response = $this->from(route('reports.create'))->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'title' => 'Kısa',
            'body' => 'Kısa',
        ]);

        $response->assertRedirect(route('reports.create'));
        $response->assertSessionHasErrors([
            'title' => 'Başlık en az 8 karakter olmalıdır.',
            'body' => 'Açıklama en az 50 karakter olmalıdır.',
        ]);
    }

    public function test_evidence_original_filename_is_sanitized_before_storage_metadata_is_saved(): void
    {
        Storage::fake('private');
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye', 'region_id' => $region->id]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'entity_id' => $entity->id,
            'title' => 'Girne asfalt dosya kanıtı',
            'body' => 'Girne bölgesindeki asfalt sorunu için kanıt dosyası eklenmiş, yeterince uzun açıklama metni.',
            'evidence_files' => [
                UploadedFile::fake()->create('../kotu<script>.jpg', 24, 'image/jpeg'),
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $filename = CorruptionReport::query()
            ->firstOrFail()
            ->evidenceFiles()
            ->firstOrFail()
            ->original_filename;

        $this->assertSame('kotuscript.jpg', $filename);
    }

    public function test_evidence_files_are_downloadable_only_by_authorized_panel_users(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('evidence/test/proof.pdf', 'secret');

        $report = CorruptionReport::query()->create([
            'title' => 'Kanıt dosyası bulunan başvuru',
            'body' => 'Kanıt dosyası indirme yetkisini test eden yeterli uzunlukta metin.',
        ]);

        $file = $report->evidenceFiles()->create([
            'original_filename' => 'proof.pdf',
            'encrypted_storage_path' => 'evidence/test/proof.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 6,
            'uploaded_at' => now(),
        ]);

        $this->get(route('evidence-files.download', $file))
            ->assertRedirect(route('login'));

        $citizen = User::factory()->create();
        $citizen->assignRole('reporter');

        $this->actingAs($citizen)
            ->get(route('evidence-files.download', $file))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('evidence-files.download', $file))
            ->assertOk();

        $this->assertDatabaseHas('moderation_logs', [
            'loggable_type' => \App\Models\EvidenceFile::class,
            'loggable_id' => $file->id,
            'actor_id' => $admin->id,
            'action' => 'evidence_file_downloaded',
        ]);
    }

    public function test_authenticated_citizen_submission_is_linked_to_their_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('reporter');
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye', 'region_id' => $region->id]);

        $this->actingAs($user)
            ->post(route('reports.store'), [
                'intake_type' => 'complaint',
                'issue_area' => 'municipal_services',
                'region_id' => $region->id,
                'entity_id' => $entity->id,
                'title' => 'Belediye hizmeti aksıyor',
                'body' => 'Mahallede düzenli temizlik yapılmadığı için uzun süredir ciddi mağduriyet yaşanıyor.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('corruption_reports', [
            'assigned_reporter_id' => $user->id,
            'title' => 'Belediye hizmeti aksıyor',
        ]);
    }

    public function test_public_submission_requires_region_or_entity(): void
    {
        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'title' => 'Mahalle yolunda asfalt sorunu',
            'body' => 'Mahalle yolunda uzun süredir devam eden ciddi asfalt ve kaldırım sorunu vardır. Araçlar ve yayalar için risk oluşturuyor.',
        ]);

        $response->assertSessionHasErrors('region_id');
        $this->assertDatabaseCount('corruption_reports', 0);
    }

    public function test_local_service_reports_reject_unrelated_entities(): void
    {
        $region = Region::query()->create(['name' => 'Lefkoşa', 'type' => 'il']);
        $electricity = Entity::query()->create([
            'name' => 'Kıbrıs Türk Elektrik Kurumu',
            'category' => 'kamu kurumu',
            'region_id' => $region->id,
        ]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'entity_id' => $electricity->id,
            'title' => 'Mahalle yolunda asfalt sorunu',
            'body' => 'Mahalle yolunda uzun süredir giderilmeyen asfalt sorunu araçlara zarar veriyor ve yayalar için tehlike oluşturuyor.',
        ]);

        $response->assertSessionHasErrors('entity_id');
        $this->assertDatabaseCount('corruption_reports', 0);
    }

    public function test_local_service_reports_reject_municipality_from_another_region(): void
    {
        $girne = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $lefkosia = Region::query()->create(['name' => 'Lefkoşa', 'type' => 'il']);
        $municipality = Entity::query()->create([
            'name' => 'Lefkoşa Türk Belediyesi',
            'category' => 'belediye',
            'region_id' => $lefkosia->id,
        ]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $girne->id,
            'entity_id' => $municipality->id,
            'title' => 'Bölge ve belediye eşleşmiyor',
            'body' => 'Değiştirilmiş bir istekle farklı bölgedeki belediyenin seçilmesini engelleyen yeterli uzunlukta metin.',
        ]);

        $response->assertSessionHasErrors('entity_id');
        $this->assertDatabaseCount('corruption_reports', 0);
    }

    public function test_citizenship_reports_require_a_target_entity(): void
    {
        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'citizenship_residency',
            'title' => 'Kurumsuz vatandaşlık başvurusu',
            'body' => 'Vatandaşlık başvurusunun ilgili kurum seçilmeden gönderilmesini engellemek için yeterli uzunlukta metin.',
        ]);

        $response->assertSessionHasErrors('entity_id');
        $this->assertDatabaseCount('corruption_reports', 0);
    }

    public function test_citizenship_reports_can_target_population_office_without_region_requirement(): void
    {
        $region = Region::query()->create(['name' => 'Lefkoşa', 'type' => 'il']);
        $populationOffice = Entity::query()->create([
            'name' => 'Nüfus Kayıt Dairesi',
            'category' => 'kamu kurumu',
            'region_id' => $region->id,
        ]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'citizenship_residency',
            'entity_id' => $populationOffice->id,
            'title' => 'Vatandaşlık sürecinde dosya bekliyor',
            'body' => 'Vatandaşlık başvuru sürecinde uzun süredir cevap verilmediği için ciddi mağduriyet yaşanıyor ve dosyanın durumu öğrenilemiyor.',
        ]);

        $response->assertSessionHasNoErrors();

        $report = CorruptionReport::query()->firstOrFail();

        $this->assertSame($populationOffice->id, $report->entity_id);
        $this->assertNull($report->region_id);
    }

    public function test_health_reports_can_target_health_center_without_region_requirement(): void
    {
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $healthCenter = Entity::query()->create([
            'name' => 'Sağlık Ocağı - Girne',
            'category' => 'sağlık',
            'region_id' => $region->id,
        ]);

        $response = $this->post(route('reports.store'), [
            'intake_type' => 'complaint',
            'issue_area' => 'health',
            'entity_id' => $healthCenter->id,
            'title' => 'Sağlık ocağı hizmetinde sorun',
            'body' => 'Sağlık ocağında uzun süredir randevu ve hizmet aksaması yaşandığı için hastalar mağdur oluyor.',
        ]);

        $response->assertSessionHasNoErrors();

        $report = CorruptionReport::query()->firstOrFail();

        $this->assertSame($healthCenter->id, $report->entity_id);
        $this->assertNull($report->region_id);
    }

    public function test_tracking_result_shows_report_summary(): void
    {
        $region = Region::query()->create(['name' => 'Lefkoşa', 'type' => 'il']);
        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'title' => 'Lefkoşa yol bakım şikâyeti',
            'body' => 'Lefkoşa bölgesinde devam eden yol bakım sorunu mahalle sakinlerini uzun süredir olumsuz etkiliyor.',
        ]);

        $this->post(route('reports.track'), ['tracking_code' => $report->tracking_code])
            ->assertOk()
            ->assertSee('Lefkoşa yol bakım şikâyeti')
            ->assertSee('Yollar, asfalt ve kaldırım')
            ->assertSee($report->tracking_code);
    }

    public function test_published_reports_are_filterable_and_have_public_detail_pages(): void
    {
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $entity = Entity::query()->create(['name' => 'Girne Belediyesi', 'category' => 'belediye', 'region_id' => $region->id]);

        $report = CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'entity_id' => $entity->id,
            'title' => 'Girne yolunda çukur problemi',
            'body' => 'Girne ana yolunda uzun süredir giderilmeyen çukurlar araçlara zarar veriyor ve trafik güvenliğini tehlikeye atıyor.',
            'public_body' => 'Girne ana yolundaki çukurlar trafik güvenliğini tehlikeye atıyor.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        $this->get(route('reports.index', ['issue_area' => 'roads_asphalt']))
            ->assertOk()
            ->assertSee('Girne yolunda çukur problemi')
            ->assertSee('Girne Belediyesi');

        $this->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Girne yolunda çukur problemi')
            ->assertSee('Yollar, asfalt ve kaldırım')
            ->assertSee('Girne Belediyesi');
    }

    public function test_municipality_page_lists_municipalities_with_published_local_issue_counts(): void
    {
        $editor = User::factory()->create();
        $legal = User::factory()->create();
        $region = Region::query()->create(['name' => 'Girne', 'type' => 'il']);
        $municipality = Entity::query()->create([
            'name' => 'Girne Belediyesi',
            'category' => 'belediye',
            'region_id' => $region->id,
        ]);

        CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'roads_asphalt',
            'region_id' => $region->id,
            'entity_id' => $municipality->id,
            'title' => 'Girne asfalt bakım itirazı',
            'body' => 'Mahalle yolundaki bozuk asfalt için yapılan başvurular sonuçsuz kaldı.',
            'public_body' => 'Mahalle yolundaki bozuk asfalt için yapılan başvurular sonuçsuz kaldı.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        CorruptionReport::query()->create([
            'intake_type' => 'complaint',
            'issue_area' => 'citizenship_residency',
            'entity_id' => $municipality->id,
            'title' => 'Sayım dışı vatandaşlık kaydı',
            'body' => 'Belediye hizmetleri sayımına girmemesi gereken kayıt.',
            'public_body' => 'Belediye hizmetleri sayımına girmemesi gereken kayıt.',
            'editor_approved_by' => $editor->id,
            'editor_approved_at' => now(),
            'legal_approved_by' => $legal->id,
            'legal_approved_at' => now(),
            'published_at' => now(),
            'status' => CorruptionReportStatus::Published,
        ]);

        $this->get(route('municipalities.index'))
            ->assertOk()
            ->assertSee('Girne Belediyesi')
            ->assertSee('1 yayınlanmış itiraz')
            ->assertDontSee('Sayım dışı vatandaşlık kaydı');
    }
    public function test_municipality_lists_prioritize_main_five_before_other_municipalities(): void
    {
        foreach ([
            'Lefke Belediyesi',
            'Tatlısu Belediyesi',
            'İskele Belediyesi',
            'Girne Belediyesi',
            'Güzelyurt Belediyesi',
            'Gazimağusa Belediyesi',
            'Lefkoşa Türk Belediyesi',
            'Gönyeli-Alayköy Belediyesi',
        ] as $name) {
            Entity::query()->create(['name' => $name, 'category' => 'belediye']);
        }

        $this->get(route('municipalities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Lefkoşa Türk Belediyesi',
                'Gazimağusa Belediyesi',
                'Girne Belediyesi',
                'Güzelyurt Belediyesi',
                'İskele Belediyesi',
                'Gönyeli-Alayköy Belediyesi',
                'Lefke Belediyesi',
                'Tatlısu Belediyesi',
            ]);
    }
}
