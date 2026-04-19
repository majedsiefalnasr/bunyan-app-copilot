<?php

namespace Tests\Unit\Services;

use App\Events\CategoryCreated;
use App\Events\CategoryMoved;
use App\Events\CategoryReordered;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = app(CategoryService::class);
    }

    public function test_create_generates_slug_from_name_en()
    {
        Event::fake();

        $category = $this->categoryService->create([
            'name_ar' => 'مواد البناء',
            'name_en' => 'Building Materials',
        ]);

        $this->assertEquals('building-materials', $category->slug);
        $this->assertTrue($category->is_active);
        $this->assertEquals(0, $category->version);
    }

    public function test_create_assigns_sort_order_as_max_plus_one()
    {
        Event::fake();

        $parent = Category::factory()->create(['sort_order' => 0]);
        Category::factory()->for($parent, 'parent')->create(['sort_order' => 0]);
        Category::factory()->for($parent, 'parent')->create(['sort_order' => 1]);

        $category = $this->categoryService->create([
            'name_ar' => 'الفئة الجديدة',
            'name_en' => 'New Category',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals(2, $category->sort_order);
    }

    public function test_create_dispatches_category_created_event()
    {
        Event::fake();

        $category = $this->categoryService->create([
            'name_ar' => 'الفئة',
            'name_en' => 'Category',
        ]);

        Event::assertDispatched(CategoryCreated::class, function ($event) use ($category) {
            return $event->category->id === $category->id;
        });
    }

    public function test_create_prevents_circular_reference()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->for($parent, 'parent')->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circular reference');

        $this->categoryService->create([
            'name_ar' => 'الفئة',
            'name_en' => 'Category',
            'parent_id' => $child->id,
        ]);
    }

    public function test_update_with_version_mismatch_throws_exception()
    {
        $category = Category::factory()->create(['version' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Version mismatch');

        $this->categoryService->update($category->id, [
            'name_ar' => 'الاسم الجديد',
        ], currentVersion: 99);
    }

    public function test_update_increments_version()
    {
        Event::fake();
        $category = Category::factory()->create(['version' => 0]);

        $updated = $this->categoryService->update($category->id, [
            'name_ar' => 'الاسم الجديد',
        ], currentVersion: 0);

        $this->assertEquals(1, $updated->version);
    }

    public function test_reorder_recalculates_sort_order()
    {
        Event::fake();

        $parent = Category::factory()->create();
        $cat1 = Category::factory()->for($parent, 'parent')->create(['sort_order' => 0]);
        $cat2 = Category::factory()->for($parent, 'parent')->create(['sort_order' => 1]);
        $cat3 = Category::factory()->for($parent, 'parent')->create(['sort_order' => 2]);

        // Move cat3 to position 0
        $reordered = $this->categoryService->reorder($cat3->id, 0, $cat3->version);

        $this->assertEquals(0, $reordered->sort_order);
        $this->assertEquals(1, $cat3->version + 1);

        Event::assertDispatched(CategoryReordered::class);
    }

    public function test_move_validates_no_circular_reference()
    {
        $grandmother = Category::factory()->create();
        $parent = Category::factory()->for($grandmother, 'parent')->create();
        $child = Category::factory()->for($parent, 'parent')->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circular reference: parent cannot be a descendant');

        $this->categoryService->move($grandmother->id, $child->id, $grandmother->version);
    }

    public function test_move_prevents_self_reference()
    {
        $category = Category::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot set category as its own parent');

        $this->categoryService->move($category->id, $category->id, $category->version);
    }

    public function test_move_dispatches_category_moved_event()
    {
        Event::fake();

        $parent1 = Category::factory()->create();
        $parent2 = Category::factory()->create();
        $child = Category::factory()->for($parent1, 'parent')->create();

        $this->categoryService->move($child->id, $parent2->id, $child->version);

        Event::assertDispatched(CategoryMoved::class);
    }

    public function test_delete_soft_deletes_category()
    {
        $category = Category::factory()->create();

        $this->categoryService->delete($category->id);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_slug_uniqueness_adds_counter()
    {
        Event::fake();

        $cat1 = $this->categoryService->create([
            'name_ar' => 'البناء',
            'name_en' => 'Construction',
        ]);

        $cat2 = $this->categoryService->create([
            'name_ar' => 'البناء',
            'name_en' => 'Construction',
        ]);

        $this->assertEquals('construction', $cat1->slug);
        $this->assertEquals('construction-1', $cat2->slug);
    }
}
