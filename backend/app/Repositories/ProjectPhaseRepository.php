<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProjectPhase;
use Illuminate\Database\Eloquent\Collection;

class ProjectPhaseRepository extends BaseRepository
{
    public function __construct(ProjectPhase $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a specific phase for a project.
     */
    public function findForProject(int $projectId, int $phaseId): ?ProjectPhase
    {
        return ProjectPhase::where('project_id', $projectId)
            ->where('id', $phaseId)
            ->first();
    }

    /**
     * List all phases for a project, ordered by sort_order.
     *
     * @return Collection<int, ProjectPhase>
     */
    public function listForProject(int $projectId): Collection
    {
        return ProjectPhase::where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get();
    }
}
