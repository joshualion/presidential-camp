<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    /**
     * Create a new event instance.
     */
    public function __construct($post)
    {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('posts');
    }

    public function broadcastAs()
    {
        return 'NewPostCreated';
    }

    public function broadcastWith()
    {
        return [
            'post' => [
                'id' => $this->post->id,
                'content' => $this->post->content,
                'image_url' => $this->post->image_url,
                'video_url' => $this->post->video_url,
                'created_at' => $this->post->created_at,
                'post_type' => $this->post->post_type,
                'audience' => $this->post->audience,
                'user' => [
                    'id' => $this->post->user->id,
                    'firstname' => $this->post->user->firstname,
                    'lastname' => $this->post->user->lastname,
                    'photo' => $this->post->user->photo,
                ],
                // Optional: Include location data if needed
                'country_id' => $this->post->country_id,
                'region_id' => $this->post->region_id,
                'state_id' => $this->post->state_id,
                'lga_id' => $this->post->lga_id,
                'ward_id' => $this->post->ward_id,
                'pu_id' => $this->post->pu_id,
            ],
        ];
    }


}
