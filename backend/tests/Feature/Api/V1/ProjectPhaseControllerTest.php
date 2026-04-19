<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPhaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->project = Project::factory()->create();
    }

    private function phaseUrl(string $suffix = ''): string
    {
        return "/api/v1/projects/{$this->project->id}/phases{$suffix}";
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_admin_can_list_phases(): void
    {
        $this->project->phases()->create([
            'name_ar' => 'مرحلة 1',
            'name_en' => 'Phase 1',
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)
            ->getJson($this->phaseUrl())
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_owner_can_list_phases(): void
    {
        $customer = User::factory()->customer()->create();
        $this->project->update(['owner_id' => $customer->id]);

        $this->actingAs($customer)
            ->getJson($this->phaseUrl())
            ->assertOk();
    }

    public function test_non_owner_customer_cannot_list_phases(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->getJson($this->phaseUrl())
            ->assertForbidden();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_admin_can_create_phase(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->phaseUrl(), [
                'name_ar' => 'مرحلة التأسيس',
                'name_en' => 'Foundation Phase',
                'sort_order' => 0,
            ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_create_phase(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson($this->phaseUrl(), [
                'name_ar' => 'مرحلة',
                'name_en' => 'Phase',
                'sort_order' => 0,
            ])
            ->assertForbidden();
    }

    public function test_contractor_cannot_create_phase(): void
    {
        $contractor = User::factory()->contractor()->create();

        $this->actingAs($contractor)
            ->postJson($this->phaseUrl(), [
                'name_ar' => 'مرحلة',
                'name_en' => 'Phase',
                'sort_order' => 0,
            ])
            ->assertForbidden();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_phase(): void
    {
        $phase = $this->project->phases()->create([
            'name_ar' => 'مرحلة 1',
            'name_en' => 'Phase 1',
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)
            ->putJson($this->phaseUrl("/{$phase->id}"), [
                'name_ar' => 'اسم جديد',
                'name_en' => 'New Name',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_admin_can_delete_phase(): void
    {
        $phase = $this->project->phases()->create([
            'name_ar' => 'مرحلة 1',
            'name_en' => 'Phase 1',
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson($this->phaseUrl("/{$phase->id}"))
            ->assertOk();
    }

    public function test_customer_cannot_delete_phase(): void
    {
        $customer = User::factory()->customer()->create();
        $phase = $this->project->phases()->create([
            'name_ar' => 'مرحلة 1',
            'name_en' => 'Phase 1',
            'sort_order' => 0,
        ]);

        $this->actingAs($customer)
            ->deleteJson($this->phaseUrl("/{$phase->id}"))
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_phases(): void
    {
        $this->getJson($this->phaseUrl())->assertUnauthorized();
    }
}
