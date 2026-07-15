<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookAdCreative extends Model
{
    protected $fillable = [
        'name',
        'comment_text',
        'target_url',
        'image_url',
        'is_active',
        'weight',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function commentLogs(): HasMany
    {
        return $this->hasMany(FacebookCommentLog::class);
    }
}
