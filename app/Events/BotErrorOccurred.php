<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BotErrorOccurred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $errorMessage;
    public int $chatId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $errorMessage, int $chatId)
    {
        $this->user = $user;
        $this->errorMessage = $errorMessage;
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
        return 'bot.error';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'bot_error',
            'chat_id' => $this->chatId,
            'error' => $this->errorMessage,
            'timestamp' => now()->toISOString()
        ];
    }
}
