<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignNotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'delivered_at',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(CampaignNotification::class, 'notification_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('notification', fn (Builder $q) => $q->active());
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at')->whereNull('dismissed_at');
    }
}
