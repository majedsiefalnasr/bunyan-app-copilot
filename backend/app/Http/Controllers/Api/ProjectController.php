<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\TransitionProjectStatusRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectController extends BaseController
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    /**
     * GET /api/v1/projects
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $filters = request()->only(['status', 'type', 'city', 'with_trashed']);
        $perPage = (int) request()->input('per_page', 15);

        $paginator = $this->projectService->list(request()->user(), $filters, $perPage);
        $result = ProjectResource::paginatedCollection($paginator);

        return response()->json([
            'success' => true,
            ...$result,
            'error' => null,
        ]);
    }

    /**
     * POST /api/v1/projects
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return $this->success(
            new ProjectResource($project),
            statusCode: 201,
        );
    }

    /**
     * GET /api/v1/projects/{project}
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project = $this->projectService->find($project->id);

        return $this->success(new ProjectResource($project));
    }

    /**
     * PUT /api/v1/projects/{project}
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        try {
            $project = $this->projectService->update($project->id, $request->validated());

            return $this->success(new ProjectResource($project));
        } catch (HttpException $e) {
            return $this->error(
                ApiErrorCode::WORKFLOW_INVALID_TRANSITION,
                $e->getMessage(),
            );
        }
    }

    /**
     * DELETE /api/v1/projects/{project}
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project->id);

        return $this->success(null);
    }

    /**
     * PUT /api/v1/projects/{project}/status
     */
    public function transitionStatus(TransitionProjectStatusRequest $request, Project $project): JsonResponse
    {
        $this->authorize('transitionStatus', $project);

        try {
            $newStatus = ProjectStatus::from($request->validated('status'));
            $expectedUpdatedAt = $request->validated('expected_updated_at');

            $project = $this->projectService->transitionStatus(
                $project->id,
                $newStatus,
                $expectedUpdatedAt,
            );

            return $this->success(new ProjectResource($project));
        } catch (HttpException $e) {
            $code = $e->getStatusCode() === 409
                ? ApiErrorCode::CONFLICT_ERROR
                : ApiErrorCode::WORKFLOW_INVALID_TRANSITION;

            return $this->error($code, $e->getMessage());
        }
    }

    /**
     * GET /api/v1/projects/{project}/timeline
     */
    public function timeline(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $this->projectService->timeline($project->id);

        return $this->success($data);
    }
}
