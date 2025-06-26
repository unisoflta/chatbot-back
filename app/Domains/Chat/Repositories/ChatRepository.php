<?php

namespace App\Domains\Chat\Repositories;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ChatRepository extends BaseRepository implements ChatRepositoryInterface
{
    /**
     * Constructor
     *
     * @param Chat $model
     */
    public function __construct(Chat $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all chats
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): Collection
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->get($columns);
    }

    /**
     * Get paginated chats
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
        return $query->orderBy('last_message_at', 'desc')->paginate($perPage, $columns);
    }

    /**
     * Find chat by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Chat|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?Chat
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->find($id, $columns);
    }

    /**
     * Create a new chat
     *
     * @param array $data
     * @return Chat
     */
    public function create(array $data): Chat
    {
        return parent::create($data);
    }

    /**
     * Update a chat
     *
     * @param int $id
     * @param array $data
     * @return Chat|null
     */
    public function update(int $id, array $data): ?Chat
    {
        return parent::update($id, $data);
    }

    /**
     * Delete a chat
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
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
    public function getByUserId(int $userId, int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('user_id', $userId)
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage, $columns);
    }

    /**
     * Get active chat for user
     *
     * @param int $userId
     * @param array $columns
     * @param array $with
     * @return Chat|null
     */
    public function getActiveChatByUserId(int $userId, array $columns = ['*'], array $with = []): ?Chat
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('user_id', $userId)
            ->where('status', 'active')
            ->first($columns);
    }

    /**
     * Get or create active chat for user
     *
     * @param int $userId
     * @param array $columns
     * @param array $with
     * @return Chat
     */
    public function getOrCreateActiveChat(int $userId, array $columns = ['*'], array $with = []): Chat
    {
        $chat = $this->getActiveChatByUserId($userId, $columns, $with);
        
        if (!$chat) {
            $chat = $this->create([
                'user_id' => $userId,
                'status' => 'active'
            ]);
        }
        
        return $chat;
    }

    /**
     * Get chats by status
     *
     * @param string $status
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->where('status', $status)
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage, $columns);
    }

    /**
     * Close a chat
     *
     * @param int $id
     * @return Chat|null
     */
    public function closeChat(int $id): ?Chat
    {
        return $this->update($id, ['status' => 'closed']);
    }

    /**
     * Delete chat and all its messages
     *
     * @param int $id
     * @return bool
     */
    public function deleteChatWithMessages(int $id): bool
    {
        try {
            DB::beginTransaction();

            $chat = $this->findById($id);
            
            if (!$chat) {
                return false;
            }

            // Delete all messages first (due to foreign key constraints)
            $chat->messages()->delete();
            
            // Delete the chat
            $deleted = $chat->delete();

            DB::commit();

            return $deleted;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update chat's last message timestamp
     *
     * @param int $id
     * @return Chat|null
     */
    public function updateLastMessageAt(int $id): ?Chat
    {
        return $this->update($id, ['last_message_at' => now()]);
    }
} 