<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The model instance
     *
     * @var Model
     */
    protected $model;

    /**
     * Constructor
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
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
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $with = []): ?Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->find($id, $columns);
    }

    /**
     * Find a record by ID or throw exception
     *
     * @param int $id
     * @param array $columns
     * @param array $with
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $with = []): Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->findOrFail($id, $columns);
    }

    /**
     * Find a record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function findBy(string $field, $value, array $columns = ['*'], array $with = []): ?Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->where($field, $value)->first($columns);
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        
        if ($model) {
            $model->update($data);
            return $model->fresh();
        }
        
        return null;
    }

    /**
     * Update or create a record
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        
        if ($model) {
            return $model->delete();
        }
        
        return false;
    }

    /**
     * Force delete a record
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool
    {
        $model = $this->find($id);
        
        if ($model) {
            return $model->forceDelete();
        }
        
        return false;
    }

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
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get records with conditions
     *
     * @param array $conditions
     * @param array $columns
     * @param array $with
     * @return Collection
     */
    public function getWhere(array $conditions, array $columns = ['*'], array $with = []): Collection
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get($columns);
    }

    /**
     * Get first record with conditions
     *
     * @param array $conditions
     * @param array $columns
     * @param array $with
     * @return Model|null
     */
    public function firstWhere(array $conditions, array $columns = ['*'], array $with = []): ?Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->first($columns);
    }

    /**
     * Count records
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->count();
    }

    /**
     * Check if record exists
     *
     * @param array $conditions
     * @return bool
     */
    public function exists(array $conditions): bool
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->exists();
    }

    /**
     * Get the model instance
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the model instance
     *
     * @param Model $model
     * @return void
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    /**
     * Get a new query builder instance
     *
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }
} 