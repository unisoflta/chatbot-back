<?php

namespace App\Domains\User\Services;

use App\Domains\User\DTOs\UserDTO;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;

class UserService
{
    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get all users
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAllUsers(array $columns = ['*'], array $with = []): Collection
    {
        return $this->userRepository->getAll($columns, $with);
    }

    /**
     * Get paginated users
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(
        int $perPage = 15,
        array $columns = ['*'],
        array $with = []
    ): LengthAwarePaginator {
        return $this->userRepository->paginate($perPage, $columns, $with);
    }

    /**
     * Find user by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return UserDTO|null
     */
    public function findUserById(int $id, array $columns = ['*'], array $with = []): ?UserDTO
    {
        $user = $this->userRepository->findById($id, $columns, $with);

        return $user ? UserDTO::fromModel($user) : null;
    }


    /**
     * Find user by email
     *
     * @param string $email
     * @param array $columns
     * @param array $with
     * @return UserDTO|null
     */
    public function findUserByEmail(string $email, array $columns = ['*'], array $with = []): ?UserDTO
    {
        $user = $this->userRepository->findByEmail($email, $columns, $with);

        return $user ? UserDTO::fromModel($user) : null;
    }

    /**
     * Create new user
     *
     * @param UserDTO $userDTO
     * @return UserDTO
     * @throws Exception
     */
    public function createUser(UserDTO $userDTO): UserDTO
    {
        try {
            DB::beginTransaction();

            // Check if email already exists
            if ($this->userRepository->findByEmail($userDTO->email)) {
                throw new Exception('Email already exists');
            }

            // Prepare user data
            $userData = [
                'name' => $userDTO->name,
                'email' => $userDTO->email,
                'password' => Hash::make($userDTO->password),
            ];

            // Create user
            $user = $this->userRepository->create($userData);

            // Assign role if provided
            if ($userDTO->role) {
                $user->assignRole($userDTO->role);
            }

            DB::commit();

            return UserDTO::fromModel($user);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update user
     *
     * @param int $id
     * @param UserDTO $userDTO
     * @return UserDTO|null
     * @throws Exception
     */
    public function updateUser(int $id, UserDTO $userDTO): ?UserDTO
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->findById($id);

            if (!$user) {
                return null;
            }

            // Check if email is being changed and if it already exists
            if ($userDTO->email !== $user->email && $this->userRepository->existsByEmail($userDTO->email)) {
                throw new Exception('Email already exists');
            }

            // Prepare update data
            $updateData = [
                'name' => $userDTO->name,
                'email' => $userDTO->email,
            ];

            // Include password if provided
            if ($userDTO->password) {
                $updateData['password'] = Hash::make($userDTO->password);
            }

            // Update user
            $updatedUser = $this->userRepository->update($id, $updateData);

            // Update role if provided
            if ($userDTO->role) {
                $updatedUser->syncRoles([$userDTO->role]);
            }

            DB::commit();

            return UserDTO::fromModel($updatedUser);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteUser(int $id): bool
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->findById($id);

            if (!$user) {
                return false;
            }

            // Remove all roles
            $user->syncRoles([]);

            // Delete user
            $deleted = $this->userRepository->delete($id);

            DB::commit();

            return $deleted;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update user status
     *
     * @param int $id
     * @param string $status
     * @return UserDTO|null
     */
    public function updateUserStatus(int $id, string $status): ?UserDTO
    {
        $user = $this->userRepository->update($id, ['status' => $status]);

        return $user ? UserDTO::fromModel($user) : null;
    }
}
