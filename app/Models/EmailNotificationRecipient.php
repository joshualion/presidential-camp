<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotificationRecipient extends Model
{
    protected $fillable = ['campaign_id', 'user_id', 'email', 'status', 'error_message', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function campaign()
    {
        return $this->belongsTo(EmailNotificationCampaign::class, 'campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
