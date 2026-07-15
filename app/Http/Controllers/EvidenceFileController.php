<?php

namespace App\Http\Controllers;

use App\Models\EvidenceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceFileController extends Controller
{
    public function download(Request $request, EvidenceFile $evidenceFile): StreamedResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'editor', 'legal', 'moderator']) ?? false, 403);

        $path = $evidenceFile->encrypted_storage_path;
        abort_unless(Storage::disk('private')->exists($path), 404);

        $evidenceFile->moderationLogs()->create([
            'actor_id' => $request->user()?->id,
            'action' => 'evidence_file_downloaded',
            'reason' => $evidenceFile->original_filename.' dosyası indirildi.',
        ]);

        return Storage::disk('private')->download($path, $evidenceFile->original_filename);
    }
}
