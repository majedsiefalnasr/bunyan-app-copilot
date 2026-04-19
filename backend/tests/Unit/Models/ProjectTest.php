<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_for_user_admin_sees_all(): void
    {
        $admin = User::factory()->admin()->create();
        Project::factory()->count(3)->create();

        $results = Project::forUser($admin)->get();
        $this->assertCount(3, $results);
    }

    public function test_scope_for_user_customer_sees_owned_only(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();

        Project::factory()->create(['owner_id' => $customer->id]);
        Project::factory()->create(['owner_id' => $otherCustomer->id]);

        $results = Project::forUser($customer)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($customer->id, $results->first()->owner_id);
    }

    public function test_scope_for_user_contractor_sees_empty(): void
    {
        $contractor = User::factory()->contractor()->create();
        Project::factory()->create();

        $results = Project::forUser($contractor)->get();
        $this->assertCount(0, $results);
    }

    public function test_scope_status_filters_correctly(): void
    {
        Project::factory()->create(['status' => ProjectStatus::DRAFT]);
        Project::factory()->planning()->create();
        Project::factory()->inProgress()->create();

        $results = Project::status(ProjectStatus::DRAFT)->get();
        $this->assertCount(1, $results);
    }

    public function test_scope_type_filters_correctly(): void
    {
        Project::factory()->create(['type' => ProjectType::RESIDENTIAL]);
        Project::factory()->commercial()->create();

        $results = Project::type(ProjectType::COMMERCIAL)->get();
        $this->assertCount(1, $results);
    }

    public function test_scope_city_filters_correctly(): void
    {
        Project::factory()->create(['city' => 'الرياض']);
        Project::factory()->create(['city' => 'جدة']);

        $results = Project::city('الرياض')->get();
        $this->assertCount(1, $results);
    }

    public function test_is_editable_returns_false_for_closed(): void
    {
        $project = Project::factory()->closed()->create();
        $this->assertFalse($project->isEditable());
    }

    public function test_is_editable_returns_true_for_non_closed(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::DRAFT]);
        $this->assertTrue($project->isEditable());

        $project2 = Project::factory()->inProgress()->create();
        $this->assertTrue($project2->isEditable());
    }

    public function test_casts_work_correctly(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::PLANNING,
            'type' => ProjectType::COMMERCIAL,
        ]);

        $project->refresh();
        $this->assertInstanceOf(ProjectStatus::class, $project->status);
        $this->assertInstanceOf(ProjectType::class, $project->type);
        $this->assertEquals(ProjectStatus::PLANNING, $project->status);
        $this->assertEquals(ProjectType::COMMERCIAL, $project->type);
    }

    public function test_owner_relationship(): void
    {
        $user = User::factory()->customer()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->assertInstanceOf(User::class, $project->owner);
        $this->assertEquals($user->id, $project->owner->id);
    }

    public function test_phases_relationship(): void
    {
        $project = Project::factory()->create();
        $project->phases()->create([
            'name_ar' => 'مرحلة 1',
            'name_en' => 'Phase 1',
            'sort_order' => 0,
        ]);

        $this->assertCount(1, $project->phases);
    }

    public function test_soft_delete_behavior(): void
    {
        $project = Project::factory()->create();
        $project->delete();

        $this->assertSoftDeleted($project);
        $this->assertNull(Project::find($project->id));
        $this->assertNotNull(Project::withTrashed()->find($project->id));
    }
}
