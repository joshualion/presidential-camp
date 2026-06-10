<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $senderId;
    public $participants;

    public function __construct(Conversation $conversation, int $senderId, array $participants)
    {
        $this->conversationId = $conversation->id;
        $this->senderId = $senderId;
        $this->participants = $participants;
    }

    public function broadcastOn(): array
    {
        return collect($this->participants)
            ->map(fn ($id) => new PrivateChannel("messages.user.{$id}"))
            ->all();
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
        ];
    }
}
