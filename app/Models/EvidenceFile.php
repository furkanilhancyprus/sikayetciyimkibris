<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EvidenceFile extends Model
{
    protected $fillable = [
        'corruption_report_id',
        'original_filename',
        'encrypted_storage_path',
        'mime_type',
        'size_bytes',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_storage_path' => 'encrypted',
            'uploaded_at' => 'datetime',
        ];
    }

    public function corruptionReport(): BelongsTo
    {
        return $this->belongsTo(CorruptionReport::class);
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'loggable');
    }
}
