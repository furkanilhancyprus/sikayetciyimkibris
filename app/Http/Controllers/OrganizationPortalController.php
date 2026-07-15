<?php

namespace App\Http\Controllers;

use App\Models\CorruptionReport;
use App\Models\ReportMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationPortalController extends Controller
{
    public function dashboard(): View
    {
        $this->authorizeOrganization();

        $entityId = auth()->user()->entity_id;
        $baseQuery = CorruptionReport::query()->where('entity_id', $entityId);

        return view('organization.dashboard', [
            'entity' => auth()->user()->entity,
            'totalReports' => (clone $baseQuery)->count(),
            'pendingResponses' => ReportMessage::query()
                ->where('status', 'pending')
                ->whereHas('corruptionReport', fn ($query) => $query->where('entity_id', $entityId))
                ->count(),
            'unansweredReports' => (clone $baseQuery)
                ->whereDoesntHave('messages', fn ($query) => $query->where('sender_type', 'team'))
                ->count(),
            'latestReports' => (clone $baseQuery)
                ->with(['region:id,name', 'messages:id,corruption_report_id,sender_type,status,created_at'])
                ->latest()
                ->limit(6)
                ->get(),
            'issueAreas' => CorruptionReportController::issueAreas(),
        ]);
    }

    public function reports(Request $request): View
    {
        $this->authorizeOrganization();

        $status = $request->string('status')->toString();

        $reports = CorruptionReport::query()
            ->with(['region:id,name', 'messages:id,corruption_report_id,sender_type,status,created_at'])
            ->where('entity_id', auth()->user()->entity_id)
            ->when($status === 'unanswered', fn ($query) => $query->whereDoesntHave('messages', fn ($messages) => $messages->where('sender_type', 'team')))
            ->when($status === 'pending_response', fn ($query) => $query->whereHas('messages', fn ($messages) => $messages->where('sender_type', 'team')->where('status', 'pending')))
            ->latest()
            ->get();

        return view('organization.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'issueAreas' => CorruptionReportController::issueAreas(),
        ]);
    }

    public function show(CorruptionReport $report): View
    {
        $this->authorizeOrganizationReport($report);

        $report->load(['region:id,name', 'entity:id,name', 'messages.user:id,name']);

        return view('organization.reports.show', [
            'report' => $report,
            'issueAreas' => CorruptionReportController::issueAreas(),
        ]);
    }

    public function respond(Request $request, CorruptionReport $report): RedirectResponse
    {
        $this->authorizeOrganizationReport($report);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:30', 'max:6000'],
        ]);

        $message = $report->messages()->create([
            'sender_type' => 'team',
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        $message->moderationLogs()->create([
            'actor_id' => auth()->id(),
            'action' => 'organization_response_submitted',
            'reason' => 'Kurum cevabı onay için gönderildi.',
        ]);

        return redirect()
            ->route('organization-portal.reports.show', $report)
            ->with('status', 'Cevabınız alındı. Yayınlanmadan önce platform ekibi tarafından kontrol edilecek.');
    }

    private function authorizeOrganization(): void
    {
        abort_unless(auth()->user()?->hasRole('organization') && filled(auth()->user()->entity_id), 403);
    }

    private function authorizeOrganizationReport(CorruptionReport $report): void
    {
        $this->authorizeOrganization();

        abort_unless((int) $report->entity_id === (int) auth()->user()->entity_id, 403);
    }
}
