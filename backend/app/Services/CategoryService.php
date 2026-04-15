<?php

namespace App\Services;

use App\Events\CategoryCreated;
use App\Events\CategoryDeleted;
use App\Events\CategoryMoved;
use App\Events\CategoryReordered;
use App\Events\CategoryUpdated;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $repository,
    ) {}

    /**
     * Create a new category with validation and event dispatch
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            // Generate slug from name_en
            $slug = $this->generateSlug($data['name_en']);

            // Check circular reference if parent_id is provided
            if (isset($data['parent_id']) && $data['parent_id']) {
                $this->validateNoCircularReference(null, (int) $data['parent_id']);
            }

            // Assign default sort_order (max + 1 for siblings)
            if (! isset($data['sort_order'])) {
                $parentId = $data['parent_id'] ?? null;
                $maxOrder = $this->repository
                    ->getChildren((int) $parentId, activeOnly: false)
                    ->max('sort_order') ?? -1;
                $data['sort_order'] = $maxOrder + 1;
            }

            // Ensure sort_order is numeric
            $data['sort_order'] = (int) $data['sort_order'];

            // Set defaults
            $data['slug'] = $slug;
            $data['version'] = 0;
            $data['is_active'] = $data['is_active'] ?? true;

            // Create the category
            $category = $this->repository->create($data);

            // Dispatch event
            event(new CategoryCreated($category));

            return $category;
        });
    }

    /**
     * Update an existing category with optimistic locking
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function update(int $categoryId, array $data, int $currentVersion): Category
    {
        return DB::transaction(function () use ($categoryId, $data, $currentVersion) {
            $category = $this->repository->findByIdOrFail($categoryId);

            // Check optimistic locking version
            if ($category->version !== $currentVersion) {
                throw new \Exception('Version mismatch; concurrent update detected', 409);
            }

            // Check for circular reference if parent_id is being changed
            if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
                $this->validateNoCircularReference($categoryId, (int) $data['parent_id']);
            }

            // Increment version for optimistic locking
            $data['version'] = $currentVersion + 1;

            // Update the category
            $category = $this->repository->update($category, $data);

            // Dispatch event
            event(new CategoryUpdated($category, $currentVersion));

            return $category;
        });
    }

    /**
     * Soft-delete a category
     */
    public function delete(int $categoryId): bool
    {
        return DB::transaction(function () use ($categoryId) {
            $category = $this->repository->findByIdOrFail($categoryId);

            // Delete the category (soft delete)
            $deleted = $this->repository->delete($category);

            if ($deleted) {
                event(new CategoryDeleted($category));
            }

            return $deleted;
        });
    }

    /**
     * Restore a soft-deleted category
     */
    public function restore(int $categoryId): bool
    {
        return DB::transaction(function () use ($categoryId) {
            $category = $this->repository->findByIdOrFail($categoryId)->restore();

            return (bool) $category;
        });
    }

    /**
     * Reorder a category within its sibling group with optimistic locking
     *
     * @throws \Exception
     */
    public function reorder(int $categoryId, int $newSortOrder, int $currentVersion): Category
    {
        return DB::transaction(function () use ($categoryId, $newSortOrder, $currentVersion) {
            $category = $this->repository->findByIdOrFail($categoryId);

            // Check optimistic locking version
            if ($category->version !== $currentVersion) {
                throw new \Exception('Version mismatch; concurrent update detected', 409);
            }

            // Reorder and update version
            $category = $this->repository->reorder($categoryId, $newSortOrder);

            // Dispatch event
            event(new CategoryReordered($category, $newSortOrder, $currentVersion));

            return $category;
        });
    }

    /**
     * Move a category to a different parent with optimistic locking
     *
     * @throws \Exception
     */
    public function move(int $categoryId, ?int $newParentId, int $currentVersion): Category
    {
        return DB::transaction(function () use ($categoryId, $newParentId, $currentVersion) {
            $category = $this->repository->findByIdOrFail($categoryId);

            // Check optimistic locking version
            if ($category->version !== $currentVersion) {
                throw new \Exception('Version mismatch; concurrent update detected', 409);
            }

            // Check for circular reference
            if ($newParentId !== null && $newParentId !== $category->parent_id) {
                $this->validateNoCircularReference($categoryId, $newParentId);
            }

            // Check for self-reference
            if ($newParentId === $categoryId) {
                throw new \Exception('Cannot set category as its own parent', 422);
            }

            // Update parent_id and version
            $category = $this->repository->update($category, [
                'parent_id' => $newParentId,
                'version' => $currentVersion + 1,
            ]);

            // Dispatch event
            event(new CategoryMoved($category, $newParentId, $currentVersion));

            return $category;
        });
    }

    /**
     * Generate a unique slug from a name
     */
    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;

        // Ensure uniqueness
        while ($this->repository->slugExists($slug)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Validate that setting $parentId as parent of $categoryId doesn't create a circular reference
     *
     * @param  ?int  $categoryId  The category being moved (null if new)
     * @param  int  $newParentId  The proposed parent ID
     *
     * @throws \Exception If circular reference is detected
     */
    private function validateNoCircularReference(?int $categoryId, int $newParentId): void
    {
        // If the new parent is the category itself, that's circular
        if ($categoryId === $newParentId) {
            throw new \Exception('Circular reference: category cannot be its own parent', 422);
        }

        // If the new parent is a descendant of the category, that's circular
        if ($categoryId !== null) {
            $descendants = $this->repository->getDescendants($categoryId);
            foreach ($descendants as $descendant) {
                if ($descendant->id === $newParentId) {
                    throw new \Exception('Circular reference: parent cannot be a descendant', 422);
                }
            }
        }
    }
}
