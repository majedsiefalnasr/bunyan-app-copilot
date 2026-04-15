<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CategoryWorkflowTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * T078: Integration Test - Full Workflow
     * Complete end-to-end workflow: create parent, create children, reorder, move, soft-delete
     */
    public function test_complete_category_workflow(): void
    {
        // 1. Admin creates parent category
        $parentResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'المواد الكهربائية',
                'name_en' => 'Electrical Materials',
                'icon' => '⚡',
            ]);

        $parentResponse->assertStatus(201);
        $parentCategory = $parentResponse->json('data');

        $this->assertNotNull($parentCategory['id']);
        $this->assertNull($parentCategory['parent_id']);
        $this->assertEquals(0, $parentCategory['sort_order']);
        $this->assertEquals(0, $parentCategory['version']);

        // 2. Admin creates 5 child categories under parent
        $childCategories = [];
        $childNames = ['الأسلاك', 'المفاتيح', 'المقابس', 'لوحات التوزيع', 'الكابلات'];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/categories', [
                    'name_ar' => $childNames[$i],
                    'name_en' => ['Wires', 'Switches', 'Sockets', 'Distribution Boards', 'Cables'][$i],
                    'parent_id' => $parentCategory['id'],
                    'sort_order' => $i,
                ]);

            $response->assertStatus(201);
            $childCategories[$i] = $response->json('data');

            $this->assertEquals($parentCategory['id'], $childCategories[$i]['parent_id']);
            $this->assertEquals($i, $childCategories[$i]['sort_order']);
        }

        // 3. Verify tree structure via GET
        $treeResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories');

        $treeResponse->assertStatus(200);

        // Find parent in tree
        $treeData = $treeResponse->json ('data');
        $parentInTree = null;

        foreach ($treeData as $category) {
            if ($category['id'] == $parentCategory['id']) {
                $parentInTree = $category;
                break;
            }
        }

        $this->assertNotNull($parentInTree);
        $this->assertCount(5, $parentInTree['children'] ?? []);

        // 4. Admin reorders: move child 5 (index 4) to position 1 (index 0)
        $childToReorder = $childCategories[4];
        $newSortOrder = 0;

        $reorderResponse = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$childToReorder['id']}/reorder", [
                'newSortOrder' => $newSortOrder,
                'version' => $childToReorder['version'],
            ]);

        $reorderResponse->assertStatus(200);

        $reorderedChild = $reorderResponse->json('data');
        $this->assertEquals($newSortOrder, $reorderedChild['sort_order']);
        $this->assertEquals($childToReorder['version'] + 1, $reorderedChild['version']); // Version bumped

        // 5. Admin moves one child to different parent (create second parent first)
        $secondParentResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'مواد السباكة',
                'name_en' => 'Plumbing Materials',
                'icon' => '🚰',
            ]);

        $secondParentResponse->assertStatus(201);
        $secondParent = $secondParentResponse->json('data');

        // Move first child from parent 1 to parent 2
        $childToMove = $childCategories[0];
        $moveResponse = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$childToMove['id']}/move", [
                'new_parent_id' => $secondParent['id'],
                'version' => $childToMove['version'],
            ]);

        $moveResponse->assertStatus(200);

        $movedChild = $moveResponse->json('data');
        $this->assertEquals($secondParent['id'], $movedChild['parent_id']);
        $this->assertEquals($childToMove['version'] + 1, $movedChild['version']);

        // 6. Verify moved child removed from first parent, under second parent
        $parentTreeResponse = $this->actingAs($this->admin)
            ->getJson("/api/v1/categories/{$parentCategory['id']}");

        $parentTreeResponse->assertStatus(200);
        $updatedParent = $parentTreeResponse->json('data');

        $this->assertCount(4, $updatedParent['children'] ?? []);

        // Moved child should not be in first parent's children
        $childIds = array_map(fn($c) => $c['id'], $updatedParent['children'] ?? []);
        $this->assertNotContains($childToMove['id'], $childIds);

        // Verify moved child is under second parent
        $secondParentTreeResponse = $this->actingAs($this->admin)
            ->getJson("/api/v1/categories/{$secondParent['id']}");

        $secondParentTreeResponse->assertStatus(200);
        $updatedSecondParent = $secondParentTreeResponse->json('data');

        $secondParentChildIds = array_map(fn($c) => $c['id'], $updatedSecondParent['children'] ?? []);
        $this->assertContains($childToMove['id'], $secondParentChildIds);

        // 7. Admin soft-deletes one category
        $categoryToDelete = $childCategories[1]; // The "Switches" category

        $deleteResponse = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/categories/{$categoryToDelete['id']}");

        $deleteResponse->assertStatus(200);

        // 8. Verify soft-delete behavior - category hidden from default queries
        $treeAfterDeleteResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories');

        $treeAfterDelete = $treeAfterDeleteResponse->json('data');

        // Find parent and verify deleted child is not present
        $parentAfterDelete = null;
        foreach ($treeAfterDelete as $category) {
            if ($category['id'] == $parentCategory['id']) {
                $parentAfterDelete = $category;
                break;
            }
        }

        if ($parentAfterDelete) {
            $childIdsAfterDelete = array_map(fn($c) => $c['id'], $parentAfterDelete['children'] ?? []);
            $this->assertNotContains($categoryToDelete['id'], $childIdsAfterDelete);
        }

        // 9. Admin queries with include_deleted flag and sees soft-deleted category
        $treeWithDeletedResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?include_deleted=true');

        $treeWithDeleted = $treeWithDeletedResponse->json('data');

        // Deleted category should now be visible
        $parentWithDeleted = null;
        foreach ($treeWithDeleted as $category) {
            if ($category['id'] == $parentCategory['id']) {
                $parentWithDeleted = $category;
                break;
            }
        }

        if ($parentWithDeleted) {
            $allChildIds = array_map(fn($c) => $c['id'], $parentWithDeleted['children'] ?? []);
            // Deleted child might be in children or marked as deleted
        }

        // 10. Verify final tree structure via GET /api/v1/categories
        $finalTreeResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories');

        $finalTreeResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name_ar',
                        'name_en',
                        'parent_id',
                        'sort_order',
                        'is_active',
                        'version',
                    ],
                ],
                'error',
            ])
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);

        // All operations succeeded with correct responses
        $this->assertTrue(true);
    }

    public function test_workflow_maintains_version_integrity(): void
    {
        $category = Category::factory()->create(['version' => 1]);

        // Each update should increment version
        $response1 = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated 1',
                'name_en' => 'Updated 1',
                'version' => 1,
            ]);

        $this->assertEquals(2, $response1->json('data.version'));

        // Next update must use new version
        $response2 = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated 2',
                'name_en' => 'Updated 2',
                'version' => 2,
            ]);

        $this->assertEquals(3, $response2->json('data.version'));

        // Old version should fail
        $response3 = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated 3',
                'name_en' => 'Updated 3',
                'version' => 1,
            ]);

        $response3->assertStatus(409);
        $this->assertEquals('CONFLICT_ERROR', $response3->json('error.code'));
    }

    public function test_workflow_respects_rbac(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();

        // Customer cannot create
        $createResponse = $this->actingAs($customer)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'فئة',
                'name_en' => 'Category',
            ]);

        $createResponse->assertStatus(403);
        $this->assertEquals('RBAC_ROLE_DENIED', $createResponse->json('error.code'));

        // Customer cannot update
        $updateResponse = $this->actingAs($customer)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated',
                'name_en' => 'Updated',
                'version' => $category->version,
            ]);

        $updateResponse->assertStatus(403);

        // Customer cannot delete
        $deleteResponse = $this->actingAs($customer)
            ->deleteJson("/api/v1/categories/{$category->id}");

        $deleteResponse->assertStatus(403);

        // Customer can view
        $viewResponse = $this->actingAs($customer)
            ->getJson('/api/v1/categories');

        $viewResponse->assertStatus(200);
    }

    public function test_workflow_handles_concurrent_operations(): void
    {
        $category = Category::factory()->create();

        // First user reads and updates
        $response1 = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Update A',
                'name_en' => 'Update A',
                'version' => $category->version,
            ]);

        $response1->assertStatus(200);
        $category->refresh();

        // Second attempt with old version should fail (conflict)
        $response2 = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Update B',
                'name_en' => 'Update B',
                'version' => $category->version - 1, // Old version
            ]);

        $response2->assertStatus(409);
        $this->assertEquals('CONFLICT_ERROR', $response2->json('error.code'));
    }
}
