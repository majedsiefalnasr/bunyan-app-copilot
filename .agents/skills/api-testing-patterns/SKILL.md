---
name: api-testing-patterns
description: RBAC API testing, integration tests
---

# API Testing Patterns — Bunyan

## Test Structure

```
backend/tests/
├── Unit/
│   ├── Models/
│   ├── Services/
│   └── Enums/
└── Feature/
    ├── Auth/
    │   ├── LoginTest.php
    │   └── RegisterTest.php
    ├── Projects/
    │   ├── CreateProjectTest.php
    │   └── ListProjectsTest.php
    └── Orders/
        └── CreateOrderTest.php
```

## Feature Test Pattern

```php
class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_project(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->postJson('/api/v1/projects', [
            'name' => 'مشروع جديد',
            'budget' => 500000,
            'location' => 'الرياض',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'status', 'budget'],
                'error',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'مشروع جديد',
                    'status' => 'draft',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'مشروع جديد',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_contractor_cannot_create_project(): void
    {
        $contractor = User::factory()->contractor()->create();

        $response = $this->actingAs($contractor)->postJson('/api/v1/projects', [
            'name' => 'مشروع',
            'budget' => 100000,
            'location' => 'جدة',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_project(): void
    {
        $response = $this->postJson('/api/v1/projects', [
            'name' => 'مشروع',
        ]);

        $response->assertStatus(401);
    }
}
```

## RBAC Testing Matrix

Every protected endpoint must have tests for:

1. ✅ Authorized role — success
2. ❌ Unauthorized role — 403
3. ❌ Unauthenticated — 401
4. ✅ Scoped access — user can only see own resources

## Factory Pattern

```php
class UserFactory extends Factory
{
    public function customer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Customer]);
    }

    public function contractor(): static
    {
        return $this->state(fn () => ['role' => UserRole::Contractor]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }
}
```

## Workflow State Transition Tests

```php
public function test_project_can_transition_from_draft_to_active(): void
{
    $project = Project::factory()->draft()->create();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->patchJson("/api/v1/projects/{$project->id}/status", [
            'status' => 'active',
        ]);

    $response->assertOk();
    $this->assertEquals('active', $project->fresh()->status->value);
}

public function test_project_cannot_skip_to_completed_from_draft(): void
{
    $project = Project::factory()->draft()->create();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->patchJson("/api/v1/projects/{$project->id}/status", [
            'status' => 'completed',
        ]);

    $response->assertStatus(422);
}
```
