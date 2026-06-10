<?php

namespace App\Events;

use App\Models\ActivityNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewActivityNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public ActivityNotification $notification;

    public function __construct(ActivityNotification $notification)
    {
        $this->notification = $notification->load('actor');
    }

    public function broadcastOn(): array
    {
        $scope = $this->notification->scope;
        $id = $this->notification->scope_id;

        if ($scope === 'public') {
            return [new PrivateChannel('activity-public')];
        }

        return [new PrivateChannel("activity-{$scope}-{$id}")];
    }

    public function broadcastWith(): array
    {
        $actor = $this->notification->actor;
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'scope' => $this->notification->scope,
            'scope_id' => $this->notification->scope_id,
            'created_at' => $this->notification->created_at?->toIso8601String(),
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? '')),
                'avatar' => $actor->photo ? url('uploads/member_images/' . $actor->photo) : url('uploads/no_image.jpg'),
            ] : null,
            'data' => $this->notification->data ?? [],
        ];
    }
}
