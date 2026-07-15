<?php

namespace App\Http\Controllers;

use App\Enums\CorruptionReportStatus;
use App\Http\Requests\StoreCorruptionReportRequest;
use App\Http\Requests\TrackCorruptionReportRequest;
use App\Models\CorruptionReport;
use App\Models\Entity;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CorruptionReportController extends Controller
{
    public function home(): View
    {
        return view('welcome', [
            'stats' => [
                'total_reports' => CorruptionReport::query()->count(),
                'published_reports' => CorruptionReport::query()->where('status', CorruptionReportStatus::Published)->count(),
                'entities' => Entity::query()->count(),
                'regions' => Region::query()->count(),
            ],
            'recentReports' => CorruptionReport::query()
                ->with(['region:id,name', 'entity:id,name,category'])
                ->where('status', CorruptionReportStatus::Published)
                ->latest('published_at')
                ->limit(4)
                ->get(),
            'talkedReports' => CorruptionReport::query()
                ->with(['region:id,name', 'entity:id,name,category'])
                ->withCount('messages')
                ->where('status', CorruptionReportStatus::Published)
                ->orderByDesc('messages_count')
                ->latest('published_at')
                ->limit(6)
                ->get(),
            'trendingMunicipalities' => Entity::query()
                ->where('category', 'belediye')
                ->withCount([
                    'corruptionReports as published_reports_count' => fn ($query) => $query
                        ->where('status', CorruptionReportStatus::Published)
                        ->whereIn('issue_area', self::municipalIssueAreaKeys()),
                ])
                ->get(['id', 'name', 'category'])
                ->sort(function (Entity $left, Entity $right): int {
                    if ($left->published_reports_count !== $right->published_reports_count) {
                        return $right->published_reports_count <=> $left->published_reports_count;
                    }

                    return self::compareEntities($left, $right);
                })
                ->take(10)
                ->values(),
            'issueAreas' => self::issueAreas(),
        ]);
    }

    public function sitemap()
    {
        $reports = CorruptionReport::query()
            ->where('status', CorruptionReportStatus::Published)
            ->latest('published_at')
            ->get(['tracking_code', 'updated_at', 'published_at']);

        $entities = Entity::query()
            ->whereHas('corruptionReports', fn ($query) => $query->where('status', CorruptionReportStatus::Published))
            ->get(['id', 'updated_at']);

        return response()
            ->view('sitemap', [
                'staticUrls' => [
                    ['loc' => route('home'), 'lastmod' => now()],
                    ['loc' => route('reports.index'), 'lastmod' => $reports->max('published_at') ?: now()],
                    ['loc' => route('municipalities.index'), 'lastmod' => $entities->max('updated_at') ?: now()],
                    ['loc' => route('privacy'), 'lastmod' => now()],
                ],
                'reports' => $reports,
                'entities' => $entities,
            ])
            ->header('Content-Type', 'application/xml');
    }

    public function create(): View
    {
        return view('reports.create', [
            'regions' => self::orderedRegions(['id', 'name', 'type', 'parent_id']),
            'entities' => self::orderedEntities(['id', 'name', 'category', 'region_id']),
            'entityGroups' => self::orderedEntityGroups(['id', 'name', 'category', 'region_id']),
            'issueAreas' => self::issueAreas(),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'issue_area' => ['nullable', 'string', 'max:80'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'intake_type' => ['nullable', 'in:complaint,report'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $reports = CorruptionReport::query()
            ->with(['region:id,name', 'entity:id,name,category'])
            ->where('status', CorruptionReportStatus::Published)
            ->when($filters['issue_area'] ?? null, fn ($query, $value) => $query->where('issue_area', $value))
            ->when($filters['region_id'] ?? null, fn ($query, $value) => $query->where('region_id', $value))
            ->when($filters['entity_id'] ?? null, fn ($query, $value) => $query->where('entity_id', $value))
            ->when($filters['intake_type'] ?? null, fn ($query, $value) => $query->where('intake_type', $value))
            ->when($filters['q'] ?? null, function ($query, $value): void {
                $query->where(function ($query) use ($value): void {
                    $query
                        ->where('title', 'like', "%{$value}%")
                        ->orWhereHas('entity', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('region', fn ($query) => $query->where('name', 'like', "%{$value}%"));
                });
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'issueAreas' => self::issueAreas(),
            'issueDescriptions' => self::issueDescriptions(),
            'regions' => self::orderedRegions(['id', 'name', 'type', 'parent_id']),
            'entities' => self::orderedEntities(['id', 'name', 'category']),
            'entityGroups' => self::orderedEntityGroups(['id', 'name', 'category']),
            'filters' => $filters,
        ]);
    }

    public function municipalities(): View
    {
        $issueAreas = self::issueAreas();
        $municipalities = Entity::query()
            ->where('category', 'belediye')
            ->withCount([
                'corruptionReports as published_reports_count' => fn ($query) => $query
                    ->where('status', CorruptionReportStatus::Published)
                    ->whereIn('issue_area', self::municipalIssueAreaKeys()),
            ])
            ->get(['id', 'name', 'category', 'region_id'])
            ->sort(fn (Entity $left, Entity $right): int => self::compareEntities($left, $right))
            ->values();

        return view('municipalities.index', [
            'municipalities' => $municipalities,
            'municipalIssueAreas' => collect($issueAreas)
                ->only([
                    ...self::municipalIssueAreaKeys(),
                ])
                ->all(),
        ]);
    }

    public function show(CorruptionReport $report): View
    {
        abort_unless($report->status === CorruptionReportStatus::Published, 404);

        $report->load([
            'region:id,name',
            'entity:id,name,category',
            'messages' => fn ($query) => $query->where('status', 'approved')->with('user:id,name'),
            'moderationLogs.actor:id,name',
        ]);

        return view('reports.show', [
            'report' => $report,
            'issueAreas' => self::issueAreas(),
            'timeline' => $this->publicTimeline($report),
            'canGiveSolutionFeedback' => $this->canGiveSolutionFeedback($report),
        ]);
    }

    public function entity(Entity $entity): View
    {
        $entity->load('region:id,name');

        $publishedReports = CorruptionReport::query()
            ->where('entity_id', $entity->id)
            ->where('status', CorruptionReportStatus::Published);

        $totalPublished = (clone $publishedReports)->count();
        $answeredCount = (clone $publishedReports)
            ->whereHas('messages', fn ($query) => $query->where('sender_type', 'team')->where('status', 'approved'))
            ->count();
        $solvedCount = (clone $publishedReports)->where('solution_status', 'solved')->count();
        $unresolvedCount = (clone $publishedReports)->where('solution_status', 'unresolved')->count();

        $issueCounts = (clone $publishedReports)
            ->select('issue_area', DB::raw('COUNT(*) as total'))
            ->groupBy('issue_area')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $recentReports = (clone $publishedReports)
            ->with(['region:id,name', 'messages:id,corruption_report_id,sender_type,status'])
            ->withCount([
                'messages as approved_responses_count' => fn ($query) => $query
                    ->where('sender_type', 'team')
                    ->where('status', 'approved'),
            ])
            ->latest('published_at')
            ->limit(8)
            ->get();

        return view('entities.show', [
            'entity' => $entity,
            'issueAreas' => self::issueAreas(),
            'issueCounts' => $issueCounts,
            'recentReports' => $recentReports,
            'stats' => [
                'total' => $totalPublished,
                'answered' => $answeredCount,
                'solved' => $solvedCount,
                'unresolved' => $unresolvedCount,
                'response_rate' => $totalPublished > 0 ? round(($answeredCount / $totalPublished) * 100) : 0,
                'solution_rate' => ($solvedCount + $unresolvedCount) > 0 ? round(($solvedCount / ($solvedCount + $unresolvedCount)) * 100) : 0,
            ],
        ]);
    }

    public function solutionFeedback(Request $request, CorruptionReport $report): RedirectResponse
    {
        abort_unless($this->canGiveSolutionFeedback($report), 403);

        $validated = $request->validate([
            'solution_status' => ['required', Rule::in(['solved', 'unresolved'])],
            'solution_feedback' => ['nullable', 'string', 'max:1200'],
        ]);

        $report->forceFill([
            'solution_status' => $validated['solution_status'],
            'solution_feedback' => $validated['solution_feedback'] ?? null,
            'solution_feedback_by' => auth()->id(),
            'solution_feedback_at' => now(),
        ])->save();

        $report->moderationLogs()->create([
            'actor_id' => auth()->id(),
            'action' => $validated['solution_status'] === 'solved' ? 'solution_marked_solved' : 'solution_marked_unresolved',
            'reason' => $validated['solution_feedback'] ?? 'Vatandaş çözüm geri bildirimi verdi.',
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('status', 'Geri bildirimin alındı. Çözüm durumu dosyaya işlendi.');
    }

    public function store(StoreCorruptionReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $consentText = __('reports.disclosure_consent_text');

        $report = DB::transaction(function () use ($request, $validated, $consentText): CorruptionReport {
            $report = CorruptionReport::query()->create([
                'intake_type' => $validated['intake_type'],
                'issue_area' => $validated['issue_area'],
                'entity_id' => $validated['entity_id'] ?? null,
                'region_id' => $validated['region_id'] ?? null,
                'title' => $validated['title'],
                'body' => $validated['body'],
                'reporter_name' => $validated['reporter_name'] ?? null,
                'reporter_contact' => $validated['reporter_contact'] ?? null,
                'identity_disclosed' => (bool) ($validated['identity_disclosed'] ?? false),
                'disclosure_consent_at' => ($validated['identity_disclosed'] ?? false) ? now() : null,
                'disclosure_consent_text' => ($validated['identity_disclosed'] ?? false) ? $consentText : null,
                'assigned_reporter_id' => $request->user()?->id,
            ]);

            foreach ($request->file('evidence_files', []) as $file) {
                $path = $file->store("evidence/{$report->tracking_code}", 'private');
                $originalName = str($file->getClientOriginalName())
                    ->replaceMatches('/[^\pL\pN._ -]+/u', '')
                    ->limit(180, '')
                    ->toString();

                $report->evidenceFiles()->create([
                    'original_filename' => $originalName !== '' ? $originalName : 'evidence-file',
                    'encrypted_storage_path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);
            }

            return $report;
        });

        return redirect()
            ->route('reports.submitted', $report)
            ->with('tracking_code', $report->tracking_code);
    }

    public function submitted(CorruptionReport $report): View
    {
        abort_unless(session('tracking_code') === $report->tracking_code, 404);

        return view('reports.submitted', ['trackingCode' => $report->tracking_code]);
    }

    public function trackForm(): View
    {
        return view('reports.track');
    }

    public function track(TrackCorruptionReportRequest $request): View
    {
        $report = CorruptionReport::query()
            ->with(['region:id,name', 'entity:id,name,category'])
            ->where('tracking_code', strtoupper($request->validated('tracking_code')))
            ->firstOrFail();

        return view('reports.track-result', [
            'report' => $report,
            'issueAreas' => self::issueAreas(),
            'publicStatus' => $this->publicStatus($report->status),
        ]);
    }

    private function publicStatus(CorruptionReportStatus $status): string
    {
        return match ($status) {
            CorruptionReportStatus::NeedsMoreInfo => __('reports.public_status.needs_more_info'),
            CorruptionReportStatus::Published => __('reports.public_status.published'),
            CorruptionReportStatus::Rejected => __('reports.public_status.rejected'),
            default => __('reports.public_status.under_review'),
        };
    }

    private function canGiveSolutionFeedback(CorruptionReport $report): bool
    {
        return auth()->check()
            && (int) $report->assigned_reporter_id === (int) auth()->id()
            && $report->status === CorruptionReportStatus::Published
            && $report->messages()
                ->where('sender_type', 'team')
                ->where('status', 'approved')
                ->exists();
    }

    /**
     * @return array<string, string>
     */
    public static function issueAreas(): array
    {
        return [
            'roads_asphalt' => 'Yollar, asfalt ve kaldırım',
            'municipal_services' => 'Belediye hizmetleri',
            'garbage_environment' => 'Çevre, çöp ve temizlik',
            'water_sewerage' => 'Su, kanalizasyon ve altyapı',
            'electricity' => 'Elektrik ve aydınlatma',
            'health' => 'Sağlık hizmetleri',
            'education' => 'Eğitim ve okul sorunları',
            'transport_traffic' => 'Ulaşım, trafik ve toplu taşıma',
            'zoning_construction' => 'İmar, inşaat ve ruhsat',
            'public_procurement' => 'İhale, usulsüzlük ve kamu zararı',
            'consumer_company' => 'Şirket, fatura ve tüketici mağduriyeti',
            'labor_social_security' => 'Çalışma izni, iş ve sosyal güvenlik',
            'other' => 'Diğer',
            'citizenship_residency' => 'Vatandaşlık, muhaceret ve oturum',
        ];
    }

    /**
     * @param  array<int, string>  $columns
     */
    private static function orderedEntities(array $columns)
    {
        $rank = [
            'bakanlık' => 1,
            'belediye' => 2,
            'kamu kurumu' => 3,
            'sağlık' => 4,
            'eğitim' => 5,
            'hizmet' => 6,
            'şirket' => 7,
        ];

        return Entity::query()
            ->get($columns)
            ->sort(function (Entity $left, Entity $right) use ($rank): int {
                $leftRank = $rank[self::normalizeEntityCategory($left->category)] ?? 99;
                $rightRank = $rank[self::normalizeEntityCategory($right->category)] ?? 99;

                if ($leftRank !== $rightRank) {
                    return $leftRank <=> $rightRank;
                }

                return self::compareEntities($left, $right);
            })
            ->values();
    }

    public static function orderedEntityOptions(): array
    {
        return self::orderedEntities(['id', 'name', 'category'])
            ->pluck('name', 'id')
            ->all();
    }

    public static function orderedRegionOptions(): array
    {
        return self::orderedRegions(['id', 'name', 'type', 'parent_id'])
            ->pluck('name', 'id')
            ->all();
    }

    private static function compareEntities(Entity $left, Entity $right): int
    {
        $leftCategory = self::normalizeEntityCategory($left->category ?? '');
        $rightCategory = self::normalizeEntityCategory($right->category ?? '');

        if ($leftCategory === 'belediye' && $rightCategory === 'belediye') {
            $leftRank = self::priorityMunicipalityRank($left->name);
            $rightRank = self::priorityMunicipalityRank($right->name);

            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }
        }

        return mb_strtolower($left->name) <=> mb_strtolower($right->name);
    }

    private static function priorityMunicipalityRank(string $name): int
    {
        return [
            'Lefkoşa Türk Belediyesi' => 1,
            'Gazimağusa Belediyesi' => 2,
            'Girne Belediyesi' => 3,
            'Güzelyurt Belediyesi' => 4,
            'İskele Belediyesi' => 5,
        ][$name] ?? 100;
    }

    private static function municipalIssueAreaKeys(): array
    {
        return [
            'roads_asphalt',
            'municipal_services',
            'garbage_environment',
            'water_sewerage',
            'electricity',
            'transport_traffic',
            'zoning_construction',
            'public_procurement',
        ];
    }

    /**
     * @param  array<int, string>  $columns
     */
    private static function orderedEntityGroups(array $columns)
    {
        return self::orderedEntities($columns)
            ->groupBy(fn (Entity $entity) => self::displayEntityCategory(self::normalizeEntityCategory($entity->category)));
    }

    private static function normalizeEntityCategory(string $category): string
    {
        return match (str($category)->lower()->replace('_', ' ')->toString()) {
            'bakanlik', 'bakanlık' => 'bakanlık',
            'belediye' => 'belediye',
            'kamu kurumu' => 'kamu kurumu',
            'saglik', 'sağlık' => 'sağlık',
            'egitim', 'eğitim' => 'eğitim',
            'hizmet' => 'hizmet',
            'sirket', 'şirket' => 'şirket',
            default => str($category)->lower()->replace('_', ' ')->toString(),
        };
    }

    private static function displayEntityCategory(string $category): string
    {
        return match ($category) {
            'bakanlık' => 'Bakanlıklar',
            'belediye' => 'Belediyeler',
            'kamu kurumu' => 'Kamu Kurumları',
            'sağlık' => 'Sağlık',
            'eğitim' => 'Eğitim',
            'hizmet' => 'Hizmetler',
            'şirket' => 'Şirketler',
            default => str($category)->title()->toString(),
        };
    }

    /**
     * @param  array<int, string>  $columns
     */
    private static function orderedRegions(array $columns)
    {
        return Region::query()
            ->get($columns)
            ->sort(function (Region $left, Region $right): int {
                $leftRank = self::priorityRegionRank($left);
                $rightRank = self::priorityRegionRank($right);

                if ($leftRank !== $rightRank) {
                    return $leftRank <=> $rightRank;
                }

                return mb_strtolower($left->name) <=> mb_strtolower($right->name);
            })
            ->values();
    }

    private static function priorityRegionRank(Region $region): int
    {
        return [
            'Lefkoşa' => 1,
            'Gazimağusa' => 2,
            'Girne' => 3,
            'Güzelyurt' => 4,
            'İskele' => 5,
        ][$region->name] ?? 100;
    }

    /**
     * @return array<string, string>
     */
    private static function issueDescriptions(): array
    {
        return [
            'citizenship_residency' => 'Vatandaşlık, muhaceret, oturum izni ve tamamlanmış evraklara rağmen bekleyen süreçler.',
            'roads_asphalt' => 'Bozuk yol, çukur, asfalt, kaldırım ve trafik güvenliği sorunları.',
            'municipal_services' => 'Belediye hizmetleri, izinler, temizlik, yerel altyapı ve hizmet aksaklıkları.',
            'garbage_environment' => 'Çöp toplama, çevre kirliliği, haşere, kötü koku ve temizlik eksikleri.',
            'water_sewerage' => 'Su kesintisi, kanalizasyon, altyapı arızası ve drenaj sorunları.',
            'electricity' => 'Elektrik kesintisi, sokak aydınlatması, fatura ve bağlantı sorunları.',
            'health' => 'Hastane, randevu, tedavi, ilaç ve sağlık hizmetlerinde yaşanan mağduriyetler.',
            'education' => 'Okul, kayıt, ulaşım, burs, yurt ve eğitim hizmetleriyle ilgili sorunlar.',
        ];
    }

    /**
     * @return array<int, array{label: string, date: \Illuminate\Support\Carbon|null, detail: string}>
     */
    private function publicTimeline(CorruptionReport $report): array
    {
        $items = [[
            'label' => 'Başvuru alındı',
            'date' => $report->created_at,
            'detail' => 'Takip kodu oluşturuldu ve dosya sisteme kaydedildi.',
        ]];

        foreach ($report->moderationLogs->sortBy('created_at') as $log) {
            $items[] = [
                'label' => match ($log->action) {
                    'start_review' => 'İncelemeye alındı',
                    'request_more_info' => 'Ek bilgi istendi',
                    'editor_approve' => 'Editör onayı verildi',
                    'legal_approve' => 'Hukuk onayı verildi',
                    'publish' => 'Yayınlandı',
                    'reject' => 'Reddedildi',
                    default => 'İşlem yapıldı',
                },
                'date' => $log->created_at,
                'detail' => $log->reason,
            ];
        }

        foreach ($report->messages->where('status', 'approved')->sortBy('created_at') as $message) {
            $items[] = [
                'label' => 'Kurum cevabı yayınlandı',
                'date' => $message->created_at,
                'detail' => $message->user?->name ?? $report->entity?->name ?? 'Kurum yetkilisi',
            ];
        }

        return collect($items)->sortBy('date')->values()->all();
    }
}
