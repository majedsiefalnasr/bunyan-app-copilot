<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return $this->model->query()->where('email', $email)->first();
    }

    /** @return Collection<int, User> */
    public function findActiveUsers(): Collection
    {
        /** @var User $userModel */
        $userModel = $this->model;

        return $userModel->active()->get();
    }
}
