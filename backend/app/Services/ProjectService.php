<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {}

    /**
     * List projects with role-scoped visibility and filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->paginateForUser($user, $filters, $perPage);
    }

    /**
     * Find project by ID with phases and owner loaded.
     *
     * @throws HttpException
     */
    public function find(int $id): Project
    {
        $project = $this->projectRepository->findWithPhases($id);

        if (! $project) {
            throw new HttpException(
                ApiErrorCode::RESOURCE_NOT_FOUND->httpStatus(),
                ApiErrorCode::RESOURCE_NOT_FOUND->defaultMessage(),
            );
        }

        return $project;
    }

    /**
     * Create a new project with default DRAFT status.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        $data['status'] = ProjectStatus::DRAFT->value;
        $data['budget_actual'] = $data['budget_actual'] ?? 0.00;

        $project = $this->projectRepository->create($data);

        /** @var Project $project */
        return $project->load('owner');
    }

    /**
     * Update an existing project.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws HttpException
     */
    public function update(int $id, array $data): Project
    {
        $project = $this->findOrFail($id);

        if (! $project->isEditable()) {
            throw new HttpException(
                ApiErrorCode::WORKFLOW_INVALID_TRANSITION->httpStatus(),
                __('projects.errors.closed_immutable'),
            );
        }

        $this->projectRepository->update($id, $data);

        return $this->projectRepository->findWithPhases($id);
    }

    /**
     * Transition project status with optimistic locking.
     *
     * @throws HttpException
     */
    public function transitionStatus(int $id, ProjectStatus $newStatus, string $expectedUpdatedAt): Project
    {
        $project = $this->findOrFail($id);

        // Optimistic locking: check updated_at matches expected value
        $expected = Carbon::parse($expectedUpdatedAt);
        if (! $project->updated_at->equalTo($expected)) {
            throw new HttpException(
                ApiErrorCode::CONFLICT_ERROR->httpStatus(),
                ApiErrorCode::CONFLICT_ERROR->defaultMessage(),
            );
        }

        // Validate transition
        if (! $project->status->canTransitionTo($newStatus)) {
            throw new HttpException(
                ApiErrorCode::WORKFLOW_INVALID_TRANSITION->httpStatus(),
                ApiErrorCode::WORKFLOW_INVALID_TRANSITION->defaultMessage(),
            );
        }

        $project->update(['status' => $newStatus->value]);

        return $project->fresh()->load('owner');
    }

    /**
     * Get project timeline data.
     *
     * @return array<string, mixed>
     */
    public function timeline(int $id): array
    {
        $project = $this->find($id);

        return [
            'project' => [
                'id' => $project->id,
                'name_ar' => $project->name_ar,
                'name_en' => $project->name_en,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'status' => $project->status->value,
            ],
            'phases' => $project->phases->map(fn ($phase) => [
                'id' => $phase->id,
                'name_ar' => $phase->name_ar,
                'name_en' => $phase->name_en,
                'start_date' => $phase->start_date?->toDateString(),
                'end_date' => $phase->end_date?->toDateString(),
                'status' => $phase->status->value,
                'completion_percentage' => $phase->completion_percentage,
                'sort_order' => $phase->sort_order,
            ])->toArray(),
        ];
    }

    /**
     * Soft-delete a project.
     */
    public function delete(int $id): bool
    {
        $this->findOrFail($id);

        return $this->projectRepository->delete($id);
    }

    /**
     * Find project or throw 404.
     *
     * @throws HttpException
     */
    private function findOrFail(int $id): Project
    {
        /** @var Project|null $project */
        $project = $this->projectRepository->find($id);

        if (! $project) {
            throw new HttpException(
                ApiErrorCode::RESOURCE_NOT_FOUND->httpStatus(),
                ApiErrorCode::RESOURCE_NOT_FOUND->defaultMessage(),
            );
        }

        return $project;
    }
}
