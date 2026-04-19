<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ReorderCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Store a newly created category
     * POST /api/v1/categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'error' => null,
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get all categories (tree structure)
     * GET /api/v1/categories
     */
    public function index(): JsonResponse
    {
        try {
            $parentId = request()->query('parent_id');
            $activeOnly = request()->boolean('active_only', true);

            if ($parentId !== null) {
                // Return children of specific parent
                $repository = app(CategoryRepository::class);
                $categories = $repository->getChildren((int) $parentId, $activeOnly);
            } else {
                // Return full tree
                $repository = app(CategoryRepository::class);
                $categories = $repository->getTree(
                    includeDeleted: false,
                    activeOnly: $activeOnly
                );
            }

            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($categories),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get a specific category
     * GET /api/v1/categories/{id}
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->load('children');

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Update a category
     * PUT /api/v1/categories/{id}
     */
    public function update(int $id, UpdateCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->update(
                $id,
                $request->validated(),
                (int) $request->input('version')
            );

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Delete a category (soft delete)
     * DELETE /api/v1/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Authorize admin only
            $user = request()->user();
            if (! $user || $user->role !== UserRole::ADMIN) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'code' => 'RBAC_ROLE_DENIED',
                        'message' => 'Your current role does not allow this action',
                    ],
                ], 403);
            }

            $this->categoryService->delete($id);

            return response()->json([
                'success' => true,
                'data' => null,
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Reorder a category
     * PUT /api/v1/categories/{id}/reorder
     */
    public function reorder(int $id, ReorderCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->reorder(
                $id,
                (int) $request->input('sort_order'),
                (int) $request->input('version')
            );

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Move a category to a different parent
     * PUT /api/v1/categories/{id}/move
     */
    public function move(int $id, UpdateCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->move(
                $id,
                $request->input('parent_id') !== null ? (int) $request->input('parent_id') : null,
                (int) $request->input('version')
            );

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Handle exceptions and return error responses
     */
    private function errorResponse(\Exception $e): JsonResponse
    {
        $status = intval($e->getCode()) ?: 500;
        $message = $e->getMessage();

        // Determine error code based on message or exception type
        $errorCode = $this->getErrorCode($e, $message);

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ], $status);
    }

    /**
     * Map exceptions to error codes
     */
    private function getErrorCode(\Exception $e, string $message): string
    {
        if ($e instanceof ModelNotFoundException) {
            return 'RESOURCE_NOT_FOUND';
        }

        if ($e instanceof ValidationException) {
            return 'VALIDATION_ERROR';
        }

        if (str_contains($message, 'Circular reference')) {
            return 'WORKFLOW_INVALID_TRANSITION';
        }

        if (str_contains($message, 'Version mismatch')) {
            return 'CONFLICT_ERROR';
        }

        if (str_contains($message, 'parent')) {
            return 'WORKFLOW_INVALID_TRANSITION';
        }

        return 'SERVER_ERROR';
    }
}
