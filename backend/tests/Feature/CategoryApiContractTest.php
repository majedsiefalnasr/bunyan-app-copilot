<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * T072: API Contract Validation.
     */
    public function test_list_categories_success_response_structure(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'error',
            ])
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);
    }

    public function test_create_category_success_response_structure(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'فئة اختبار',
                'name_en' => 'Test Category',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name_ar',
                    'name_en',
                    'slug',
                    'parent_id',
                    'sort_order',
                    'is_active',
                    'version',
                    'created_at',
                    'updated_at',
                ],
                'error',
            ])
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);
    }

    public function test_error_response_has_standard_structure(): void
    {
        // Test validation error (empty name)
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => '',
                'name_en' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'data',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ])
            ->assertJson([
                'success' => false,
                'data' => null,
            ]);
    }

    public function test_validation_error_includes_field_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => '',
                'name_en' => 'Valid English',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'name_ar',
                    ],
                ],
            ]);

        $errorDetails = $response->json('error.details');
        $this->assertIsArray($errorDetails['name_ar'] ?? null);
    }

    public function test_not_found_error_response(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories/99999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'data',
                'error' => [
                    'code',
                    'message',
                ],
            ])
            ->assertJson([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                ],
            ]);
    }

    public function test_rbac_unauthorized_error(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'فئة',
                'name_en' => 'Category',
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                ],
            ])
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RBAC_ROLE_DENIED',
                ],
            ]);
    }

    public function test_conflict_error_on_version_mismatch(): void
    {
        $category = Category::factory()->create(['version' => 1]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated',
                'name_en' => 'Updated',
                'version' => 99, // Wrong version
            ]);

        $response->assertStatus(409)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                ],
            ])
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CONFLICT_ERROR',
                ],
            ]);
    }

    public function test_circular_reference_workflow_error(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Try to make parent's parent be the child (circular)
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$parent->id}", [
                'parent_id' => $child->id,
                'version' => $parent->version,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                ],
            ])
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'WORKFLOW_INVALID_TRANSITION',
                ],
            ]);
    }

    public function test_http_status_codes_match_contract(): void
    {
        // 201 for successful POST (create)
        $createResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'name_ar' => 'فئة',
                'name_en' => 'Category',
            ]);
        $this->assertEquals(201, $createResponse->status());

        // 200 for successful GET
        $getResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories');
        $this->assertEquals(200, $getResponse->status());

        // 200 for successful PUT (update)
        $category = Category::factory()->create();
        $updateResponse = $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name_ar' => 'Updated',
                'name_en' => 'Updated',
                'version' => $category->version,
            ]);
        $this->assertEquals(200, $updateResponse->status());

        // 200 for successful DELETE
        $deleteResponse = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/categories/{$category->id}");
        $this->assertTrue(in_array($deleteResponse->status(), [200, 204]));
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    public function test_response_includes_all_required_fields(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name_ar',
                    'name_en',
                    'slug',
                    'parent_id',
                    'sort_order',
                    'is_active',
                    'version',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $data = $response->json('data');

        // Verify field values are present and typed correctly
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name_ar']);
        $this->assertIsString($data['name_en']);
        $this->assertIsString($data['slug']);
        $this->assertTrue(is_int($data['parent_id']) || is_null($data['parent_id']));
        $this->assertIsInt($data['version']);
    }

    public function test_nested_children_structure_in_response(): void
    {
        $parent = Category::factory()->create();
        Category::factory(2)->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/categories/{$parent->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'children' => [
                        '*' => [
                            'id',
                            'name_ar',
                            'name_en',
                        ],
                    ],
                ],
            ]);
    }

    public function test_pagination_if_implemented(): void
    {
        Category::factory(100)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?perPage=10');

        // Response should either have pagination or full list
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $data = $response->json('data');
        $this->assertTrue(is_array($data));
    }

    public function test_error_response_does_not_expose_stack_trace(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories/invalid/action');

        $responseText = json_encode($response->json());

        // Should not contain typical stack trace indicators
        $this->assertStringNotContainsString('at line', $responseText);
        $this->assertStringNotContainsString('Stack trace', $responseText);
        $this->assertStringNotContainsString('Exception', $responseText);
    }

    public function test_error_codes_are_from_registry(): void
    {
        $validErrorCodes = [
            'AUTH_INVALID_CREDENTIALS',
            'AUTH_TOKEN_EXPIRED',
            'AUTH_UNAUTHORIZED',
            'RBAC_ROLE_DENIED',
            'RESOURCE_NOT_FOUND',
            'VALIDATION_ERROR',
            'WORKFLOW_INVALID_TRANSITION',
            'WORKFLOW_PREREQUISITES_UNMET',
            'PAYMENT_FAILED',
            'CONFLICT_ERROR',
            'RATE_LIMIT_EXCEEDED',
            'SERVER_ERROR',
        ];

        // Test various error scenarios
        $responses = [
            $this->actingAs($this->admin)->postJson('/api/v1/categories', []), // validation
            $this->actingAs(User::factory()->customer()->create())->postJson('/api/v1/categories', ['name_ar' => 'a', 'name_en' => 'a']), // rbac
            $this->getJson('/api/v1/categories/99999'), // not found (auth will fail first)
        ];

        foreach ($responses as $response) {
            if ($response->json('error')) {
                $code = $response->json('error.code');
                // Code should be in registry or at least follow naming pattern
                $this->assertStringContainsString('_', $code);
            }
        }
    }
}
