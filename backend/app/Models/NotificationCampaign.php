<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'audience',
        'title',
        'body',
        'type',
        'image_path',
        'created_by',
        'sent_at',
        'last_sent_at',
        'resend_count',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_campaign_recipients', 'campaign_id', 'user_id')
            ->withTimestamps();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationCampaignRead::class, 'campaign_id');
    }
}

