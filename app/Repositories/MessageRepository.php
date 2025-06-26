<?php

namespace App\Repositories;

use App\Domains\Messages\Models\Message;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageRepository extends BaseRepository implements MessageRepositoryInterface
{
    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all messages
     *
     * @param array $columns
     * @param array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->get($columns);
    }

    /**
     * Get all messages with pagination
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->orderBy('created_at', 'desc')->paginate($perPage, $columns);
    }

    /**
     * Find message by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Message|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?Message
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->find($id, $columns);
    }

    /**
     * Create a new message
     *
     * @param array $data
     * @return Message
     */
    public function create(array $data): Message
    {
        return $this->model->create($data);
    }

    /**
     * Update a message
     *
     * @param int $id
     * @param array $data
     * @return Message|null
     */
    public function update(int $id, array $data): ?Message
    {
        $message = $this->findById($id);
        
        if (!$message) {
            return null;
        }

        $message->update($data);
        return $message->fresh();
    }

    /**
     * Delete a message
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $message = $this->findById($id);
        
        if (!$message) {
            return false;
        }

        return $message->delete();
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
    public function getByChatId(int $chatId, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('chat_id', $chatId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $columns);
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
    public function getByUserId(int $userId, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->whereHas('chat', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $columns);
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
        return $this->model
            ->where('chat_id', $chatId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->sender_type === 'user' ? 'user' : 'assistant',
                    'content' => $message->content
                ];
            })
            ->toArray();
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
    public function getBySenderType(string $senderType, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('sender_type', $senderType)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $columns);
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
    public function searchByContent(string $query, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('content', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $columns);
    }
} 