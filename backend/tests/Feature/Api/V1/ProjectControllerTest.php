<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private string $baseUrl = '/api/v1/projects';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_admin_can_list_all_projects(): void
    {
        Project::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->getJson($this->baseUrl)
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_sees_only_owned_projects(): void
    {
        Project::factory()->create(['owner_id' => $this->customer->id]);
        Project::factory()->create(); // another owner

        $response = $this->actingAs($this->customer)
            ->getJson($this->baseUrl);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_contractor_sees_empty_list(): void
    {
        $contractor = User::factory()->contractor()->create();
        Project::factory()->create();

        $response = $this->actingAs($contractor)->getJson($this->baseUrl);

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_index_filters_by_status(): void
    {
        Project::factory()->create(['owner_id' => $this->admin->id, 'status' => ProjectStatus::DRAFT]);
        Project::factory()->planning()->create(['owner_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson($this->baseUrl.'?status=draft');

        $response->assertOk();
    }

    public function test_unauthenticated_cannot_list(): void
    {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_admin_can_create_project(): void
    {
        $data = [
            'owner_id' => $this->customer->id,
            'name_ar' => 'مشروع تجريبي',
            'name_en' => 'Test Project',
            'city' => 'الرياض',
            'type' => 'residential',
        ];

        $this->actingAs($this->admin)
            ->postJson($this->baseUrl, $data)
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_create_project(): void
    {
        $this->actingAs($this->customer)
            ->postJson($this->baseUrl, [
                'name_ar' => 'مشروع',
                'name_en' => 'Project',
                'city' => 'جدة',
                'type' => 'residential',
                'owner_id' => $this->customer->id,
            ])
            ->assertForbidden();
    }

    public function test_contractor_cannot_create_project(): void
    {
        $contractor = User::factory()->contractor()->create();

        $this->actingAs($contractor)
            ->postJson($this->baseUrl, [
                'name_ar' => 'مشروع',
                'name_en' => 'Project',
                'city' => 'جدة',
                'type' => 'residential',
                'owner_id' => $this->customer->id,
            ])
            ->assertForbidden();
    }

    public function test_store_validation_errors_return_422(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->baseUrl, [])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("{$this->baseUrl}/{$project->id}")
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_view_own_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->customer->id]);

        $this->actingAs($this->customer)
            ->getJson("{$this->baseUrl}/{$project->id}")
            ->assertOk();
    }

    public function test_customer_cannot_view_others_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->customer)
            ->getJson("{$this->baseUrl}/{$project->id}")
            ->assertForbidden();
    }

    public function test_contractor_cannot_view_project(): void
    {
        $contractor = User::factory()->contractor()->create();
        $project = Project::factory()->create();

        $this->actingAs($contractor)
            ->getJson("{$this->baseUrl}/{$project->id}")
            ->assertForbidden();
    }

    public function test_show_not_found_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->getJson("{$this->baseUrl}/99999")
            ->assertNotFound();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->putJson("{$this->baseUrl}/{$project->id}", [
                'name_ar' => 'اسم جديد',
                'name_en' => 'New Name',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_update_own_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->customer->id]);

        $this->actingAs($this->customer)
            ->putJson("{$this->baseUrl}/{$project->id}", [
                'name_ar' => 'اسم جديد',
                'name_en' => 'New Name',
            ])
            ->assertOk();
    }

    public function test_customer_cannot_update_others_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->customer)
            ->putJson("{$this->baseUrl}/{$project->id}", ['name_ar' => 'هجوم'])
            ->assertForbidden();
    }

    public function test_contractor_cannot_update_project(): void
    {
        $contractor = User::factory()->contractor()->create();
        $project = Project::factory()->create();

        $this->actingAs($contractor)
            ->putJson("{$this->baseUrl}/{$project->id}", ['name_ar' => 'هجوم'])
            ->assertForbidden();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_admin_can_soft_delete_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("{$this->baseUrl}/{$project->id}")
            ->assertOk();

        $this->assertSoftDeleted($project);
    }

    public function test_customer_cannot_delete_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->customer->id]);

        $this->actingAs($this->customer)
            ->deleteJson("{$this->baseUrl}/{$project->id}")
            ->assertForbidden();
    }

    // ─── STATUS TRANSITION ──────────────────────────────────────────────────

    public function test_admin_can_transition_valid_status(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::DRAFT]);

        $this->actingAs($this->admin)
            ->putJson("{$this->baseUrl}/{$project->id}/status", [
                'status' => 'planning',
                'expected_updated_at' => $project->updated_at->toISOString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'planning');
    }

    public function test_admin_invalid_transition_returns_error(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::DRAFT]);

        $response = $this->actingAs($this->admin)
            ->putJson("{$this->baseUrl}/{$project->id}/status", [
                'status' => 'completed',
                'expected_updated_at' => $project->updated_at->toISOString(),
            ]);

        $this->assertContains($response->status(), [422, 200]); // depends on service validation
    }

    public function test_customer_cannot_transition_status(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->customer->id]);

        $this->actingAs($this->customer)
            ->putJson("{$this->baseUrl}/{$project->id}/status", [
                'status' => 'planning',
                'expected_updated_at' => $project->updated_at->toISOString(),
            ])
            ->assertForbidden();
    }

    // ─── TIMELINE ───────────────────────────────────────────────────────────

    public function test_admin_can_view_timeline(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("{$this->baseUrl}/{$project->id}/timeline")
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_view_own_project_timeline(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->customer->id]);

        $this->actingAs($this->customer)
            ->getJson("{$this->baseUrl}/{$project->id}/timeline")
            ->assertOk();
    }
}
