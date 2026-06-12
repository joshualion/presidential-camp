<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'message',
        'actor_id',
        'subject_type',
        'subject_id',
        'action_url',
        'severity',
        'region_id',
        'state_id',
        'senatorial_district_id',
        'federal_constituency_id',
        'lga_id',
        'ward_id',
        'polling_unit_id',
        'metadata',
        'occurred_at',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function recipients()
    {
        return $this->hasMany(CampaignNotificationRecipient::class, 'notification_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    protected static function booted(): void
    {
        static::creating(function (self $notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }
}
