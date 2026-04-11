<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function find(int $id): ?Model;

    public function findAll(): Collection;

    /**
     * @param  array<string, mixed>  $criteria  Pre-validated criteria (never raw HTTP input).
     */
    public function findBy(array $criteria): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
