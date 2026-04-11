<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * @param  array<string, mixed>  $criteria  Pre-validated criteria (never raw HTTP input).
     */
    public function findBy(array $criteria): Collection
    {
        return $this->model->where($criteria)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->model->find($id);

        if ($record === null) {
            throw new ModelNotFoundException("Record with ID {$id} not found.");
        }

        $record->update($data);

        return $record->fresh();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $record = $this->model->find($id);

        if ($record === null) {
            throw new ModelNotFoundException("Record with ID {$id} not found.");
        }

        return (bool) $record->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
}
