<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryRepository
{
    public function __construct(
        private readonly Category $model,
    ) {}

    /**
     * Get the complete category tree structure
     *
     * @param  bool  $includeDeleted  Include soft-deleted categories
     * @param  bool  $activeOnly  Only include active categories
     * @return Collection<int, Category>
     */
    public function getTree(bool $includeDeleted = false, bool $activeOnly = true): Collection
    {
        $query = $this->model->query();

        if ($includeDeleted) {
            $query->withTrashed();
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query
            ->whereNull('parent_id')
            ->with($this->getEagerLoadingRelations())
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get children of a specific category
     *
     * @return Collection<int, Category>
     */
    public function getChildren(int $parentId, bool $activeOnly = true): Collection
    {
        $query = $this->model->query()
            ->where('parent_id', $parentId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get all ancestors (parents, grandparents, etc.)
     *
     * @return Collection<int, Category>
     */
    public function getAncestors(int $categoryId): Collection
    {
        $category = $this->findByIdOrFail($categoryId);

        return $category->getAncestors();
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     *
     * @return Collection<int, Category>
     */
    public function getDescendants(int $categoryId): Collection
    {
        $category = $this->findByIdOrFail($categoryId);

        return $category->getDescendants();
    }

    /**
     * Find a category by ID
     */
    public function findById(int $id): ?Category
    {
        return $this->model->find($id);
    }

    /**
     * Find a category by ID or throw exception
     *
     * @throws ModelNotFoundException
     */
    public function findByIdOrFail(int $id): Category
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find a category by slug
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Check if a slug already exists (excluding a specific category)
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Create a new category
     */
    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing category
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    /**
     * Delete a category (soft delete)
     */
    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    /**
     * Restore a soft-deleted category
     */
    public function restore(Category $category): bool
    {
        return $category->restore();
    }

    /**
     * Permanently delete a category (force delete)
     */
    public function forceDelete(Category $category): bool|int
    {
        return $category->forceDelete();
    }

    /**
     * Reorder sibling categories and update the target category
     * Recalculates sort_order for all affected siblings
     *
     * @param  int  $categoryId  The category to move
     * @param  int  $newSortOrder  The new sort_order position
     */
    public function reorder(int $categoryId, int $newSortOrder): Category
    {
        $category = $this->findByIdOrFail($categoryId);
        $parentId = $category->parent_id;

        // Get all siblings (same parent_id, not including self)
        $siblings = $this->model
            ->where('parent_id', $parentId)
            ->where('id', '!=', $categoryId)
            ->orderBy('sort_order')
            ->get();

        // Recalculate sort_order for all siblings and target
        $newOrder = 0;
        $found = false;

        foreach ($siblings as $sibling) {
            if (! $found && $newOrder === $newSortOrder) {
                // Insert target category at this position
                $category->update([
                    'sort_order' => $newOrder,
                    'version' => $category->version + 1,
                    'updated_at' => now(),
                ]);
                $found = true;
                $newOrder++;
            }

            $sibling->update([
                'sort_order' => $newOrder,
                'updated_at' => now(),
            ]);
            $newOrder++;
        }

        // If not yet positioned (new position is at end)
        if (! $found) {
            $category->update([
                'sort_order' => $newOrder,
                'version' => $category->version + 1,
                'updated_at' => now(),
            ]);
        }

        return $category->fresh();
    }

    /**
     * Get eager loading relations to prevent N+1 queries
     *
     * @return array<string, callable>
     */
    private function getEagerLoadingRelations(): array
    {
        return [
            'children' => fn ($q) => $q
                ->active()
                ->ordered()
                ->with($this->getEagerLoadingRelations()),
        ];
    }
}
