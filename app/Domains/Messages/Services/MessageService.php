<?php

namespace App\Domains\Messages\Services;

use App\Domains\Chat\Models\Chat;
use App\Domains\Messages\DTOs\MessageDTO;
use App\Domains\Messages\Models\Message;
use App\Jobs\ProcessChatMessage;
use App\Models\User;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Services\GPTChatService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MessageService
{
    /**
     * Constructor
     *
     * @param MessageRepositoryInterface $messageRepository
     * @param GPTChatService $gptChatService
     */
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private GPTChatService $gptChatService
    ) {}

    /**
     * Get all messages
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAllMessages(array $columns = ['*'], array $with = []): Collection
    {
        return $this->messageRepository->getAll($columns, $with);
    }

    /**
     * Get paginated messages
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getPaginatedMessages(
        int $perPage = 15,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->messageRepository->getAllPaginated($perPage, $columns, $with);
    }

    /**
     * Find message by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return MessageDTO|null
     */
    public function findMessageById(int $id, array $columns = ['*'], array $with = []): ?MessageDTO
    {
        $message = $this->messageRepository->findById($id, $columns, $with);
        
        return $message ? MessageDTO::fromModel($message) : null;
    }

    /**
     * Create new message
     *
     * @param MessageDTO $messageDTO
     * @return MessageDTO
     * @throws Exception
     */
    public function createMessage(MessageDTO $messageDTO): MessageDTO
    {
        try {
            DB::beginTransaction();

            // Create message
            $message = $this->messageRepository->create($messageDTO->getFillableData());

            DB::commit();

            return MessageDTO::fromModel($message);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update message
     *
     * @param int $id
     * @param MessageDTO $messageDTO
     * @return MessageDTO|null
     * @throws Exception
     */
    public function updateMessage(int $id, MessageDTO $messageDTO): ?MessageDTO
    {
        try {
            DB::beginTransaction();

            $message = $this->messageRepository->findById($id);
            
            if (!$message) {
                return null;
            }

            // Update message
            $updatedMessage = $this->messageRepository->update($id, $messageDTO->getFillableData());

            DB::commit();

            return $updatedMessage ? MessageDTO::fromModel($updatedMessage) : null;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete message
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteMessage(int $id): bool
    {
        try {
            DB::beginTransaction();

            $message = $this->messageRepository->findById($id);
            
            if (!$message) {
                return false;
            }

            // Delete message
            $deleted = $this->messageRepository->delete($id);

            DB::commit();

            return $deleted;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get messages by chat ID with pagination
     *
     * @param int $chatId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getMessagesByChatId(
        int $chatId, 
        int $perPage = 50,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->messageRepository->getByChatId($chatId, $perPage, $columns, $with);
    }

    /**
     * Get messages by user ID with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getMessagesByUserId(
        int $userId, 
        int $perPage = 50,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->messageRepository->getByUserId($userId, $perPage, $columns, $with);
    }

    /**
     * Get chat history for a specific chat
     *
     * @param int $chatId
     * @param int $limit
     * @return array
     */
    public function getChatHistory(int $chatId, int $limit = 20): array
    {
        return $this->messageRepository->getChatHistory($chatId, $limit);
    }

    /**
     * Get messages by sender type
     *
     * @param string $senderType
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getMessagesBySenderType(
        string $senderType, 
        int $perPage = 50,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->messageRepository->getBySenderType($senderType, $perPage, $columns, $with);
    }

    /**
     * Search messages by content
     *
     * @param string $query
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function searchMessagesByContent(
        string $query, 
        int $perPage = 50,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->messageRepository->searchByContent($query, $perPage, $columns, $with);
    }

    /**
     * Send a message to a chat and queue AI response processing
     *
     * @param User $user
     * @param int $chatId
     * @param string $message
     * @return array
     * @throws Exception
     */
    public function sendMessage(User $user, int $chatId, string $message): array
    {
        try {
            DB::beginTransaction();

            Log::info('📨 Starting async message processing', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'message_length' => strlen($message)
            ]);

            // Verify chat belongs to user
            $chat = $user->chats()->findOrFail($chatId);

            // Create message DTO
            $messageDTO = MessageDTO::fromArray([
                'chat_id' => $chatId,
                'sender_type' => 'user',
                'content' => $message
            ]);

            // Save user message
            $userMessage = $this->createMessage($messageDTO);

            Log::info('💾 User message saved', [
                'user_message_id' => $userMessage->chat_id
            ]);

            // Update chat's last message timestamp
            $chat->updateLastMessageAt();

            DB::commit();

            // Dispatch job to process AI response in background
            ProcessChatMessage::dispatch($user->id, $chatId, $message, $userMessage->chat_id);

            Log::info('🔄 Chat message job dispatched', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'user_message_id' => $userMessage->chat_id
            ]);

            return [
                'user_message' => $userMessage->toArray(),
                'bot_response' => null, // Will be sent via Pusher
                'chat_id' => $chatId,
                'timestamp' => now()->toISOString(),
                'status' => 'processing',
                'message' => 'Tu mensaje ha sido enviado. La respuesta del bot se procesará en segundo plano.'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get messages for a specific chat (for user)
     *
     * @param User $user
     * @param int $chatId
     * @param int $perPage
     * @return array
     */
    public function getMessagesForUser(User $user, int $chatId, int $perPage = 50): array
    {
        // Verify chat belongs to user
        $chat = $user->chats()->findOrFail($chatId);

        $messages = $this->getMessagesByChatId($chatId, $perPage);

        return [
            'messages' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total()
            ]
        ];
    }

    /**
     * Update message for user
     *
     * @param User $user
     * @param int $messageId
     * @param array $data
     * @return MessageDTO|null
     */
    public function updateMessageForUser(User $user, int $messageId, array $data): ?MessageDTO
    {
        // Verify message belongs to user
        $message = $this->messageRepository->findById($messageId);
        
        if (!$message || $message->chat->user_id !== $user->id) {
            return null;
        }

        $messageDTO = MessageDTO::fromArray($data);
        return $this->updateMessage($messageId, $messageDTO);
    }

    /**
     * Delete message for user
     *
     * @param User $user
     * @param int $messageId
     * @return bool
     */
    public function deleteMessageForUser(User $user, int $messageId): bool
    {
        // Verify message belongs to user
        $message = $this->messageRepository->findById($messageId);
        
        if (!$message || $message->chat->user_id !== $user->id) {
            return false;
        }

        return $this->deleteMessage($messageId);
    }

    /**
     * Get all messages (legacy method for backward compatibility)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPaginatedMessages($perPage);
    }

    /**
     * Find message by ID (legacy method for backward compatibility)
     *
     * @param int $id
     * @return Message|null
     */
    public function findById(int $id): ?Message
    {
        $messageDTO = $this->findMessageById($id);
        return $messageDTO ? $this->messageRepository->findById($id) : null;
    }

    /**
     * Create message (legacy method for backward compatibility)
     *
     * @param array $data
     * @return Message
     */
    public function create(array $data): Message
    {
        $messageDTO = MessageDTO::fromArray($data);
        $createdDTO = $this->createMessage($messageDTO);
        return $this->messageRepository->findById($createdDTO->chat_id);
    }

    /**
     * Update message (legacy method for backward compatibility)
     *
     * @param int $id
     * @param array $data
     * @return Message|null
     */
    public function update(int $id, array $data): ?Message
    {
        $messageDTO = MessageDTO::fromArray($data);
        $updatedDTO = $this->updateMessage($id, $messageDTO);
        return $updatedDTO ? $this->messageRepository->findById($id) : null;
    }

    /**
     * Delete message (legacy method for backward compatibility)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->deleteMessage($id);
    }

    /**
     * Get messages by chat ID (legacy method for backward compatibility)
     *
     * @param int $chatId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByChatId(int $chatId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->getMessagesByChatId($chatId, $perPage);
    }

    /**
     * Get messages by user ID (legacy method for backward compatibility)
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUserId(int $userId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->getMessagesByUserId($userId, $perPage);
    }

    /**
     * Get messages by sender type (legacy method for backward compatibility)
     *
     * @param string $senderType
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBySenderType(string $senderType, int $perPage = 50): LengthAwarePaginator
    {
        return $this->getMessagesBySenderType($senderType, $perPage);
    }

    /**
     * Search messages by content (legacy method for backward compatibility)
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchByContent(string $query, int $perPage = 50): LengthAwarePaginator
    {
        return $this->searchMessagesByContent($query, $perPage);
    }
} 