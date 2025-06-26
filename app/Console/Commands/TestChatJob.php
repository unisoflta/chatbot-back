<?php

namespace App\Console\Commands;

use App\Jobs\ProcessChatMessage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestChatJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:chat-job {user_id} {chat_id} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the chat message processing job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $chatId = $this->argument('chat_id');
        $message = $this->argument('message');

        $this->info("🧪 Testing chat job for user {$userId}, chat {$chatId}");
        $this->info("Message: {$message}");

        // Verify user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found");
            return 1;
        }

        // Verify chat exists and belongs to user
        $chat = $user->chats()->find($chatId);
        if (!$chat) {
            $this->error("❌ Chat with ID {$chatId} not found or doesn't belong to user {$userId}");
            return 1;
        }

        $this->info("✅ User and chat verified successfully");

        // Create a dummy user message ID (in real scenario this would be created by MessageService)
        $userMessageId = 999999; // Dummy ID for testing

        // Dispatch the job
        $this->info("🔄 Dispatching ProcessChatMessage job...");
        ProcessChatMessage::dispatch($userId, $chatId, $message, $userMessageId);

        $this->info("✅ Job dispatched successfully!");
        $this->info("📡 Check your queue worker and Pusher for the response");
        $this->info("💡 Run 'php artisan queue:work' to process the job");

        return 0;
    }
}
