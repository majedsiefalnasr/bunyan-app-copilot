<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name_ar' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'icon' => null,
            'sort_order' => 0,
            'is_active' => true,
            'version' => 0,
        ];
    }

    /**
     * Create a root (top-level) category
     */
    public function root(): self
    {
        return $this->state(fn () => [
            'parent_id' => null,
        ]);
    }

    /**
     * Create a nested category with a parent
     */
    public function nested(): self
    {
        return $this->state(function () {
            /** @var Category $parent */
            $parent = Category::factory()->root()->create();

            return [
                'parent_id' => $parent->getKey(),
            ];
        });
    }

    /**
     * Create an inactive category
     */
    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a soft-deleted category
     */
    public function deleted(): self
    {
        return $this->state(fn () => [
            'deleted_at' => now(),
        ]);
    }
}
