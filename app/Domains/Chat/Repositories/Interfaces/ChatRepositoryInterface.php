<?php

namespace App\Domains\Chat\Repositories\Interfaces;

use App\Domains\Chat\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChatRepositoryInterface
{
    /**
     * Get all chats
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): Collection;

    /**
     * Get paginated chats
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Find chat by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Chat|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?Chat;

    /**
     * Create a new chat
     *
     * @param array $data
     * @return Chat
     */
    public function create(array $data): Chat;

    /**
     * Update a chat
     *
     * @param int $id
     * @param array $data
     * @return Chat|null
     */
    public function update(int $id, array $data): ?Chat;

    /**
     * Delete a chat
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get chats by user ID with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getByUserId(int $userId, int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Get active chat for user
     *
     * @param int $userId
     * @param array $columns
     * @param array $with
     * @return Chat|null
     */
    public function getActiveChatByUserId(int $userId, array $columns = ['*'], array $with = []): ?Chat;

    /**
     * Get or create active chat for user
     *
     * @param int $userId
     * @param array $columns
     * @param array $with
     * @return Chat
     */
    public function getOrCreateActiveChat(int $userId, array $columns = ['*'], array $with = []): Chat;

    /**
     * Get chats by status
     *
     * @param string $status
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Close a chat
     *
     * @param int $id
     * @return Chat|null
     */
    public function closeChat(int $id): ?Chat;

    /**
     * Delete chat and all its messages
     *
     * @param int $id
     * @return bool
     */
    public function deleteChatWithMessages(int $id): bool;

    /**
     * Update chat's last message timestamp
     *
     * @param int $id
     * @return Chat|null
     */
    public function updateLastMessageAt(int $id): ?Chat;
} 