<?php

namespace App\Domains\User\Repositories;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Constructor
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return parent::create($data);
    }

    /**
     * Update user
     *
     * @param int $id
     * @param array $data
     * @return User|null
     */
    public function update(int $id, array $data): ?User
    {
        return parent::update($id, $data);
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @param array $columns
     * @param array $with
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*'], array $with = []): ?User
    {
        return parent::findBy('email', $email, $columns, $with);
    }

    /**
     * Find user by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return User|null
     */
    public function findById(int $id, array $columns = ['*'], array $with = []): ?User
    {
        return $this->find($id, $columns, $with);
    }
} 