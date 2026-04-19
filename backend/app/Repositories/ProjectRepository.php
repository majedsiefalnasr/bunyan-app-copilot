<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository extends BaseRepository
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate projects scoped to user role with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Project::query()
            ->forUser($user)
            ->withCount('phases');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = ProjectStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->status($status);
            }
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            $type = ProjectType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->type($type);
            }
        }

        if (isset($filters['city']) && $filters['city'] !== '') {
            $query->city($filters['city']);
        }

        if (isset($filters['with_trashed']) && $filters['with_trashed'] && $user->hasEnumRole(UserRole::ADMIN)) {
            $query->withTrashed();
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a project with phases and owner eager-loaded.
     */
    public function findWithPhases(int $id): ?Project
    {
        return Project::with(['phases', 'owner'])->find($id);
    }
}
