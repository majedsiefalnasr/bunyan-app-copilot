<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?Role
    {
        /** @var Role|null */
        return $this->model->where('name', $name)->first();
    }

    public function findWithPermissions(int $id): ?Role
    {
        /** @var Role|null */
        return $this->model->with('permissions')->find($id);
    }
}
