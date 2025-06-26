<?php

namespace App\Events;

use App\Domains\Messages\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BotResponseReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Message $message;
    public int $chatId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Message $message, int $chatId)
    {
        $this->user = $user;
        $this->message = $message;
        $this->chatId = $chatId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}.chat.{$this->chatId}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'bot.response';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'bot_response',
            'chat_id' => $this->chatId,
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'sender_type' => $this->message->sender_type,
                'created_at' => $this->message->created_at->toISOString()
            ],
            'timestamp' => now()->toISOString()
        ];
    }
}
