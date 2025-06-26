<?php

namespace App\Domains\Chat\Services;

use App\Domains\Chat\DTOs\ChatDTO;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Repositories\Interfaces\ChatRepositoryInterface;
use App\Domains\Messages\Models\Message;
use App\Models\User;
use App\Services\GPTChatService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ChatService
{
    /**
     * Constructor
     *
     * @param ChatRepositoryInterface $chatRepository
     * @param GPTChatService $gptChatService
     */
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private GPTChatService $gptChatService
    ) {}

    /**
     * Find chat by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return ChatDTO|null
     */
    public function findChatById(int $id, array $columns = ['*'], array $with = []): ?ChatDTO
    {
        $chat = $this->chatRepository->findById($id, $columns, $with);

        return $chat ? ChatDTO::fromModel($chat) : null;
    }

    /**
     * Create new chat
     *
     * @param ChatDTO $chatDTO
     * @return ChatDTO
     * @throws Exception
     */
    public function createChat(ChatDTO $chatDTO): ChatDTO
    {
        try {
            DB::beginTransaction();

            // Create chat
            $chat = $this->chatRepository->create($chatDTO->getFillableData());

            DB::commit();

            return ChatDTO::fromModel($chat);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update chat
     *
     * @param int $id
     * @param ChatDTO $chatDTO
     * @return ChatDTO|null
     * @throws Exception
     */
    public function updateChat(int $id, ChatDTO $chatDTO): ?ChatDTO
    {
        try {
            DB::beginTransaction();

            $chat = $this->chatRepository->findById($id);

            if (!$chat) {
                return null;
            }

            // Update chat
            $updatedChat = $this->chatRepository->update($id, $chatDTO->getFillableData());

            DB::commit();

            return $updatedChat ? ChatDTO::fromModel($updatedChat) : null;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get chats by user ID with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getChatsByUserId(
        int $userId,
        int $perPage = 15,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->chatRepository->getByUserId($userId, $perPage, $columns, $with);
    }

    /**
     * Get all chats for a user with pagination (legacy method for backward compatibility)
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChats(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getChatsByUserId($user->id, $perPage, ['*'], ['messages' => function ($query) {
            $query->latest()->limit(1);
        }]);
    }

    /**
     * Close a chat (legacy method for backward compatibility)
     *
     * @param User $user
     * @param int $chatId
     * @return Chat
     * @throws Exception
     */
    public function closeChatForUser(User $user, int $chatId): Chat
    {
        $chat = $this->chatRepository->findById($chatId);

        if (!$chat || $chat->user_id !== $user->id) {
            throw new Exception('Chat not found or access denied');
        }

        $updatedChat = $this->chatRepository->closeChat($chatId);

        if (!$updatedChat) {
            throw new Exception('Failed to close chat');
        }

        return $updatedChat;
    }

    /**
     * Delete a chat and all its messages (legacy method for backward compatibility)
     *
     * @param User $user
     * @param int $chatId
     * @return bool
     * @throws Exception
     */
    public function deleteChatForUser(User $user, int $chatId): bool
    {
        $chat = $this->chatRepository->findById($chatId);

        if (!$chat || $chat->user_id !== $user->id) {
            throw new Exception('Chat not found or access denied');
        }

        return $this->chatRepository->deleteChatWithMessages($chatId);
    }
}
