<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $userId;

    public function __construct(Chat $chat, int $userId)
    {
        $this->chat = $chat;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ChatEvent.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'chat' => new ChatResource($this->chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ])),
        ];
    }
}
