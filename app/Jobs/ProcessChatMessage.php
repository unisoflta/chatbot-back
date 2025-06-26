<?php

namespace App\Jobs;

use App\Domains\Chat\Models\Chat;
use App\Domains\Messages\Models\Message;
use App\Events\BotErrorOccurred;
use App\Events\BotResponseBroadcasted;
use App\Events\BotResponseFailed;
use App\Events\BotResponseReceived;
use App\Models\User;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Services\GPTChatService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    private int $userId;
    private int $chatId;
    private string $message;
    private int $userMessageId;

    public function __construct(int $userId, int $chatId, string $message, int $userMessageId)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
        $this->message = $message;
        $this->userMessageId = $userMessageId;
    }

    public function handle(MessageRepositoryInterface $messageRepository, GPTChatService $gptChatService): void
    {
        Log::info('🔄 Processing chat message job started', [
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
            'message_id' => $this->userMessageId,
        ]);

        try {
            $user = User::findOrFail($this->userId);
            $chat = $user->chats()->findOrFail($this->chatId);
            $history = $messageRepository->getChatHistory($this->chatId, 10);

            $response = $gptChatService->handleChat($this->message, $history);

            $botMessage = $messageRepository->create([
                'chat_id' => $this->chatId,
                'sender_type' => 'bot',
                'content' => $response,
            ]);

            $chat->updateLastMessageAt();

            broadcast(new BotResponseReceived($user, $botMessage, $this->chatId));

            Log::info('🎉 Chat message processing completed successfully');
        } catch (Throwable $e) {
            Log::error('❌ Error processing chat message job', [
                'user_id' => $this->userId,
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);

            try {
                if ($user = User::find($this->userId)) {
                    broadcast(new BotErrorOccurred($user,  $e->getMessage(), $this->chatId));
                }
            } catch (Throwable $ex) {
                Log::error('❌ Error broadcasting failure', [
                    'error' => $ex->getMessage()
                ]);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('💥 Job failed permanently', [
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
            'message_id' => $this->userMessageId,
            'error' => $exception->getMessage(),
        ]);

        try {
            if ($user = User::find($this->userId)) {
                broadcast(new BotErrorOccurred($user, 'Hubo un error inesperado. Intenta nuevamente.', $this->chatId));
            }
        } catch (Throwable $ex) {
            Log::error('❌ Could not broadcast final failure', [
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
