<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPost implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;
    public $comments;
    public $reactions;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Post $post, $post_comments, $post_reactions)
    {
        //
        
        $this->post = $post;
        $this->comments = $post_comments;
        $this->reactions = $post_reactions;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
        // return new Channel('community-feed-'.$this->post->group_id);
        return ['community-feed-'.$this->post->group_id];
    }
    public function broadcastAs(){
        return 'update-feed';
    }
}
