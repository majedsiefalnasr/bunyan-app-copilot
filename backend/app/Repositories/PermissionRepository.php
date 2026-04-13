<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function findByGroup(string $group): Collection
    {
        return $this->model->where('group', $group)->get();
    }

    /**
     * @param  array<int, string>  $names
     */
    public function findByNames(array $names): Collection
    {
        return $this->model->whereIn('name', $names)->get();
    }
}
