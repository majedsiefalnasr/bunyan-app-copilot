<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProjectPhaseRequest;
use App\Http\Requests\UpdateProjectPhaseRequest;
use App\Http\Resources\ProjectPhaseResource;
use App\Models\Project;
use App\Services\ProjectPhaseService;
use Illuminate\Http\JsonResponse;

class ProjectPhaseController extends BaseController
{
    public function __construct(
        private readonly ProjectPhaseService $phaseService,
    ) {}

    /**
     * GET /api/v1/projects/{project}/phases
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $phases = $this->phaseService->listForProject($project->id);

        return $this->success(ProjectPhaseResource::collection($phases));
    }

    /**
     * POST /api/v1/projects/{project}/phases
     */
    public function store(StoreProjectPhaseRequest $request, Project $project): JsonResponse
    {
        $this->authorize('addPhase', $project);

        $phase = $this->phaseService->create($project->id, $request->validated());

        return $this->success(
            new ProjectPhaseResource($phase),
            statusCode: 201,
        );
    }

    /**
     * PUT /api/v1/projects/{project}/phases/{phase}
     */
    public function update(UpdateProjectPhaseRequest $request, Project $project, int $phase): JsonResponse
    {
        $this->authorize('addPhase', $project);

        $phase = $this->phaseService->update($project->id, $phase, $request->validated());

        return $this->success(new ProjectPhaseResource($phase));
    }

    /**
     * DELETE /api/v1/projects/{project}/phases/{phase}
     */
    public function destroy(Project $project, int $phase): JsonResponse
    {
        $this->authorize('addPhase', $project);

        $this->phaseService->delete($project->id, $phase);

        return $this->success(null);
    }
}
