<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookAutomationSetting extends Model
{
    protected $fillable = [
        'page_id',
        'page_name',
        'is_enabled',
        'approval_required',
        'check_interval_minutes',
        'min_delay_minutes',
        'max_delay_minutes',
        'max_comments_per_hour',
        'max_comments_per_day',
        'same_creative_cooldown_hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'approval_required' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'page_name' => 'Haberler KKTC',
            'is_enabled' => false,
            'approval_required' => true,
        ]);
    }
}
