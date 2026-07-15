<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookCommentLog extends Model
{
    protected $fillable = [
        'facebook_ad_creative_id',
        'facebook_post_id',
        'facebook_comment_id',
        'status',
        'message',
        'error_message',
        'scheduled_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function creative(): BelongsTo
    {
        return $this->belongsTo(FacebookAdCreative::class, 'facebook_ad_creative_id');
    }
}
