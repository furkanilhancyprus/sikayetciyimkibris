<?php

namespace App\Http\Controllers;

use App\Models\CorruptionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $reports = CorruptionReport::query()
            ->with(['entity:id,name', 'region:id,name'])
            ->where('assigned_reporter_id', auth()->id())
            ->latest()
            ->get();

        return view('account.index', [
            'reports' => $reports,
            'issueAreas' => CorruptionReportController::issueAreas(),
        ]);
    }

    public function show(CorruptionReport $report): View
    {
        $this->authorizeAccountReport($report);

        $report->load(['entity:id,name', 'region:id,name', 'messages.user:id,name']);

        return view('account.show', [
            'report' => $report,
            'issueAreas' => CorruptionReportController::issueAreas(),
        ]);
    }

    public function message(Request $request, CorruptionReport $report): RedirectResponse
    {
        $this->authorizeAccountReport($report);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:20', 'max:4000'],
        ]);

        $message = $report->messages()->create([
            'sender_type' => 'reporter',
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        $message->moderationLogs()->create([
            'actor_id' => auth()->id(),
            'action' => 'citizen_reply_submitted',
            'reason' => 'Vatandaş ek açıklaması moderasyon için gönderildi.',
        ]);

        return redirect()
            ->route('account.reports.show', $report)
            ->with('status', 'Mesajın alındı. Ekip kontrolünden sonra dosya geçmişine işlenecek.');
    }

    private function authorizeAccountReport(CorruptionReport $report): void
    {
        abort_unless((int) $report->assigned_reporter_id === (int) auth()->id(), 403);
    }
}
