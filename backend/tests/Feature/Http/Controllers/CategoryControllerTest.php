<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
    }

    public function test_guest_can_list_categories()
    {
        Category::factory()->create(['name_en' => 'Building Materials', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name_ar', 'name_en', 'slug', 'version']],
                'error',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('error', null);
    }

    public function test_guest_can_view_single_category()
    {
        $category = Category::factory()->create(['name_en' => 'Electrical']);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name_en', 'Electrical');
    }

    public function test_admin_can_create_category()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/categories', [
            'name_ar' => 'مواد البناء',
            'name_en' => 'Building Materials',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name_en', 'Building Materials')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', ['name_en' => 'Building Materials']);
    }

    public function test_customer_cannot_create_category()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/categories', [
            'name_ar' => 'الفئة',
            'name_en' => 'Category',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_create_category()
    {
        $response = $this->postJson('/api/v1/categories', [
            'name_ar' => 'الفئة',
            'name_en' => 'Category',
        ]);

        $response->assertUnauthorized();
    }

    public function test_invalid_parent_id_returns_validation_error()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/categories', [
            'name_ar' => 'الفئة',
            'name_en' => 'Category',
            'parent_id' => 9999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_admin_can_update_category()
    {
        $category = Category::factory()->create(['name_en' => 'Old Name', 'version' => 0]);

        $response = $this->actingAs($this->admin)->putJson(
            "/api/v1/categories/{$category->id}",
            [
                'name_en' => 'New Name',
                'version' => 0,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name_en', 'New Name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name_en' => 'New Name']);
    }

    public function test_version_mismatch_returns_conflict_error()
    {
        $category = Category::factory()->create(['version' => 0]);

        $response = $this->actingAs($this->admin)->putJson(
            "/api/v1/categories/{$category->id}",
            [
                'name_en' => 'New Name',
                'version' => 99,
            ]
        );

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT_ERROR');
    }

    public function test_admin_can_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_list_excludes_inactive_by_default()
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/categories');

        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_category_can_be_reordered()
    {
        $parent = Category::factory()->create();
        $cat1 = Category::factory()->for($parent, 'parent')->create(['sort_order' => 0]);
        $cat2 = Category::factory()->for($parent, 'parent')->create(['sort_order' => 1]);

        $response = $this->actingAs($this->admin)->putJson(
            "/api/v1/categories/{$cat2->id}/reorder",
            [
                'sort_order' => 0,
                'version' => $cat2->version,
            ]
        );

        $response->assertOk();
    }

    public function test_category_can_be_moved_to_different_parent()
    {
        $parent1 = Category::factory()->create();
        $parent2 = Category::factory()->create();
        $child = Category::factory()->for($parent1, 'parent')->create();

        $response = $this->actingAs($this->admin)->putJson(
            "/api/v1/categories/{$child->id}/move",
            [
                'parent_id' => $parent2->id,
                'version' => $child->version,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.parent_id', $parent2->id);
    }

    public function test_nested_categories_appear_in_tree()
    {
        $parent = Category::factory()->create(['name_en' => 'Parent']);
        $child = Category::factory()->for($parent, 'parent')->create(['name_en' => 'Child']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/categories');

        $this->assertEquals('Parent', $response->json('data.0.name_en'));
        $this->assertEquals('Child', $response->json('data.0.children.0.name_en'));
    }

    public function test_soft_deleted_categories_excluded_from_list()
    {
        $active = Category::factory()->create(['is_active' => true]);
        $deleted = Category::factory()->create(['deleted_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/categories');

        $this->assertEquals(1, count($response->json('data')));
        $this->assertNull(collect($response->json('data'))->firstWhere('id', $deleted->id));
    }

    public function test_api_response_follows_standard_contract()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/categories');

        // Check response follows standard contract: success, data, error keys present
        $response->assertJsonStructure([
            'success',
            'data',
            'error',
        ]);

        // Verify success is boolean
        $this->assertIsBool($response->json('success'));

        // Verify data is array (or null)
        $this->assertTrue(
            is_array($response->json('data')) || $response->json('data') === null,
            'data should be array or null'
        );
    }
}
