<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): Collection;

    /**
     * Find user by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return User|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?User;

    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update user
     *
     * @param int $id
     * @param array $data
     * @return User|null
     */
    public function update(int $id, array $data): ?User;

    /**
     * Delete user
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find user by email
     *
     * @param string $email
     * @param array $columns
     * @param array $with
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*'], array $with = []): ?User;
} 