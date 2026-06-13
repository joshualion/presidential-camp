<?php

namespace App\Events;

use App\Models\CampaignNotificationRecipient;
use App\Services\Notifications\CampaignNotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class CampaignNotificationCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public CampaignNotificationRecipient $recipient)
    {
        $this->recipient->loadMissing('notification');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('campaign.notifications.' . $this->recipient->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'CampaignNotificationCreated';
    }

    public function broadcastWith(): array
    {
        $notification = $this->recipient->notification;
        $user = $this->recipient->user;

        return [
            'notification_id' => $notification->id,
            'recipient_id' => $this->recipient->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'severity' => $notification->severity,
            'action_url' => $notification->action_url,
            'occurred_at' => $notification->occurred_at?->toIso8601String(),
            'expires_at' => $notification->expires_at?->toIso8601String(),
            'unread_count' => $user ? app(CampaignNotificationService::class)->unreadCount($user) : 0,
        ];
    }
}
