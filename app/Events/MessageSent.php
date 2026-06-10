<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;
    public $participants;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, array $participants)
    {
        $this->message = $message->load('sender');
        $this->conversationId = $message->conversation_id;
        $this->participants = $participants;
    }

    public function broadcastOn(): array
    {
        // broadcast to each participant's private channel
        return collect($this->participants)
            ->map(fn ($id) => new PrivateChannel("messages.user.{$id}"))
            ->all();
    }

    public function broadcastWith(): array
    {
        return [
          'conversation_id' => $this->conversationId,
          'message' => [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'sender_id' => $this->message->sender_id,
            'sender' => $this->message->sender ? [
                'id' => $this->message->sender->id,
                'firstname' => $this->message->sender->firstname,
                'lastname' => $this->message->sender->lastname,
                'username' => $this->message->sender->username,
            ] : null,
            'created_at' => $this->message->created_at,
          ],
        ];
    }
}
