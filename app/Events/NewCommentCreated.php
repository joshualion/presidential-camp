<?php
// NewCommentCreated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('posts.' . $this->comment->post_id);
    }

    public function broadcastAs()
    {
        return 'NewCommentCreated';
    }

    public function broadcastWith()
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'content' => $this->comment->content,
                'created_at' => $this->comment->created_at,
                'user' => [
                    'id' => $this->comment->user->id,
                    'firstname' => $this->comment->user->firstname,
                    'lastname' => $this->comment->user->lastname,
                    'photo' => $this->comment->user->photo,
                ],
                'parent_id' => $this->comment->parent_id,
            ],
        ];
    }
}