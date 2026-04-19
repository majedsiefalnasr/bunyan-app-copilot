<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Models\ProjectPhase;
use App\Repositories\ProjectPhaseRepository;
use App\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectPhaseService
{
    public function __construct(
        private readonly ProjectPhaseRepository $phaseRepository,
        private readonly ProjectRepository $projectRepository,
    ) {}

    /**
     * List phases for a project, ordered by sort_order.
     *
     * @return Collection<int, ProjectPhase>
     */
    public function listForProject(int $projectId): Collection
    {
        $this->ensureProjectExists($projectId);

        return $this->phaseRepository->listForProject($projectId);
    }

    /**
     * Create a new phase for a project.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $projectId, array $data): ProjectPhase
    {
        $this->ensureProjectExists($projectId);

        $data['project_id'] = $projectId;
        $data['status'] = $data['status'] ?? 'pending';
        $data['completion_percentage'] = $data['completion_percentage'] ?? 0;

        /** @var ProjectPhase $phase */
        $phase = $this->phaseRepository->create($data);

        return $phase;
    }

    /**
     * Update an existing phase.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $projectId, int $phaseId, array $data): ProjectPhase
    {
        $this->ensureProjectExists($projectId);
        $phase = $this->findPhaseOrFail($projectId, $phaseId);

        $phase->update($data);

        return $phase->fresh();
    }

    /**
     * Delete a phase (hard-delete).
     */
    public function delete(int $projectId, int $phaseId): bool
    {
        $this->ensureProjectExists($projectId);
        $phase = $this->findPhaseOrFail($projectId, $phaseId);

        return (bool) $phase->delete();
    }

    /**
     * Ensure the parent project exists.
     *
     * @throws HttpException
     */
    private function ensureProjectExists(int $projectId): void
    {
        if (! $this->projectRepository->find($projectId)) {
            throw new HttpException(
                ApiErrorCode::RESOURCE_NOT_FOUND->httpStatus(),
                ApiErrorCode::RESOURCE_NOT_FOUND->defaultMessage(),
            );
        }
    }

    /**
     * Find phase for a project or throw 404.
     *
     * @throws HttpException
     */
    private function findPhaseOrFail(int $projectId, int $phaseId): ProjectPhase
    {
        $phase = $this->phaseRepository->findForProject($projectId, $phaseId);

        if (! $phase) {
            throw new HttpException(
                ApiErrorCode::RESOURCE_NOT_FOUND->httpStatus(),
                ApiErrorCode::RESOURCE_NOT_FOUND->defaultMessage(),
            );
        }

        return $phase;
    }
}
