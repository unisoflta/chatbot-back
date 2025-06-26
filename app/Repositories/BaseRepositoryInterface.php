<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getAll(array $columns = ['*'], array $with = []): Collection;

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $with = []): ?Model;

    /**
     * Find a record by ID or throw exception
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $with = []): Model;

    /**
     * Find a record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function findBy(string $field, $value, array $columns = ['*'], array $with = []): ?Model;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function update(int $id, array $data): ?Model;

    /**
     * Update or create a record
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Force delete a record
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool;

    /**
     * Paginate records
     *
     * @param int $perPage
     * @param array $columns
     * @param array $with
     * @param string $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 15, 
        array $columns = ['*'], 
        array $with = [], 
        string $pageName = 'page', 
        ?int $page = null
    ): LengthAwarePaginator;

    /**
     * Get records with conditions
     *
     * @param array $conditions
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getWhere(array $conditions, array $columns = ['*'], array $with = []): Collection;

    /**
     * Get first record with conditions
     *
     * @param array $conditions
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function firstWhere(array $conditions, array $columns = ['*'], array $with = []): ?Model;

    /**
     * Count records
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * Check if record exists
     *
     * @param array $conditions
     * @return bool
     */
    public function exists(array $conditions): bool;

    /**
     * Get the model instance
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Set the model instance
     *
     * @param Model $model
     * @return void
     */
    public function setModel(Model $model): void;

    /**
     * Get a new query builder instance
     *
     * @return Builder
     */
    public function newQuery(): Builder;
} 