<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailNotificationCampaign extends Model
{
    protected $fillable = [
        'uuid', 'submission_token', 'sender_id', 'subject', 'title', 'body', 'cta_label', 'cta_url',
        'recipient_group', 'selected_access_levels', 'selected_roles', 'audience_filters',
        'status', 'total_recipients', 'sent_count', 'failed_count', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'selected_access_levels' => 'array',
        'selected_roles' => 'array',
        'audience_filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->hasMany(EmailNotificationRecipient::class, 'campaign_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (self $campaign) {
            $campaign->uuid ??= (string) Str::uuid();
        });
    }
}
