<?php

namespace App\Repositories\Interfaces;

use App\Domains\Messages\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;

interface MessageRepositoryInterface
{
    /**
     * Get all messages
     *
     * @param array $columns
     * @param array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get all messages with pagination
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Find message by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Message|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?Message;

    /**
     * Create a new message
     *
     * @param array $data
     * @return Message
     */
    public function create(array $data): Message;

    /**
     * Update a message
     *
     * @param int $id
     * @param array $data
     * @return Message|null
     */
    public function update(int $id, array $data): ?Message;

    /**
     * Delete a message
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get messages by chat ID with pagination
     *
     * @param int $chatId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getByChatId(int $chatId, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Get messages by user ID with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getByUserId(int $userId, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Get chat history for a specific chat
     *
     * @param int $chatId
     * @param int $limit
     * @return array
     */
    public function getChatHistory(int $chatId, int $limit = 20): array;

    /**
     * Get messages by sender type
     *
     * @param string $senderType
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getBySenderType(string $senderType, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * Search messages by content
     *
     * @param string $query
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function searchByContent(string $query, int $perPage = 50, array $columns = ['*'], array $with = []): LengthAwarePaginator;
} 