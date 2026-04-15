<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'sort_order',
        'is_active',
        'version',
    ];

    protected $casts = [
        'parent_id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'bool',
        'version' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: parent category (self-referential BelongsTo)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relationship: direct children categories (self-referential HasMany)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope: only active categories
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only top-level categories (no parent)
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: only categories without children
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->doesntHave('children');
    }

    /**
     * Scope: ordered by sort_order and created_at
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Scope: filter by parent_id
     */
    public function scopeByParent(Builder $query, ?int $parentId = null): Builder
    {
        return $parentId === null
            ? $query->whereNull('parent_id')
            : $query->where('parent_id', $parentId);
    }

    /**
     * Scope: get all categories with eager-loaded tree (recursive children)
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeForTree(Builder $query): Builder
    {
        return $query
            ->with('children')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all ancestors (parents, grandparents, etc.) of this category
     *
     * @return Collection<int, Category>
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();

        $current = $this->parent;
        while ($current !== null) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants (children, grandchildren, etc.) of this category
     *
     * @return Collection<int, Category>
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            /** @var Category $child */
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Check if this category is an ancestor of the given category
     */
    public function isAncestorOf(Category $category): bool
    {
        return $category->getAncestors()->contains($this);
    }

    /**
     * Check if this category is a descendant of the given category
     */
    public function isDescendantOf(Category $category): bool
    {
        return $this->getAncestors()->contains($category);
    }

    /**
     * Check if this category has any descendants
     */
    public function hasDescendants(): bool
    {
        return $this->children()->exists();
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
