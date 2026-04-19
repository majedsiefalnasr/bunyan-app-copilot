<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ProjectStatus;
use PHPUnit\Framework\TestCase;

class ProjectStatusTest extends TestCase
{
    public function test_all_six_values_exist(): void
    {
        $cases = ProjectStatus::cases();
        $this->assertCount(6, $cases);

        $values = array_map(fn (ProjectStatus $s) => $s->value, $cases);
        $this->assertContains('draft', $values);
        $this->assertContains('planning', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('on_hold', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('closed', $values);
    }

    public function test_label_returns_arabic_strings(): void
    {
        $this->assertEquals('مسودة', ProjectStatus::DRAFT->label());
        $this->assertEquals('تخطيط', ProjectStatus::PLANNING->label());
        $this->assertEquals('قيد التنفيذ', ProjectStatus::IN_PROGRESS->label());
        $this->assertEquals('متوقف', ProjectStatus::ON_HOLD->label());
        $this->assertEquals('مكتمل', ProjectStatus::COMPLETED->label());
        $this->assertEquals('مغلق', ProjectStatus::CLOSED->label());
    }

    public function test_allowed_transitions_draft(): void
    {
        $this->assertEquals([ProjectStatus::PLANNING], ProjectStatus::DRAFT->allowedTransitions());
    }

    public function test_allowed_transitions_planning(): void
    {
        $this->assertEquals([ProjectStatus::IN_PROGRESS], ProjectStatus::PLANNING->allowedTransitions());
    }

    public function test_allowed_transitions_in_progress(): void
    {
        $this->assertEquals(
            [ProjectStatus::ON_HOLD, ProjectStatus::COMPLETED],
            ProjectStatus::IN_PROGRESS->allowedTransitions(),
        );
    }

    public function test_allowed_transitions_on_hold(): void
    {
        $this->assertEquals([ProjectStatus::IN_PROGRESS], ProjectStatus::ON_HOLD->allowedTransitions());
    }

    public function test_allowed_transitions_completed(): void
    {
        $this->assertEquals([ProjectStatus::CLOSED], ProjectStatus::COMPLETED->allowedTransitions());
    }

    public function test_allowed_transitions_closed(): void
    {
        $this->assertEquals([], ProjectStatus::CLOSED->allowedTransitions());
    }

    public function test_can_transition_to_valid(): void
    {
        $this->assertTrue(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::PLANNING));
        $this->assertTrue(ProjectStatus::IN_PROGRESS->canTransitionTo(ProjectStatus::ON_HOLD));
        $this->assertTrue(ProjectStatus::IN_PROGRESS->canTransitionTo(ProjectStatus::COMPLETED));
    }

    public function test_can_transition_to_invalid(): void
    {
        $this->assertFalse(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::COMPLETED));
        $this->assertFalse(ProjectStatus::CLOSED->canTransitionTo(ProjectStatus::DRAFT));
        $this->assertFalse(ProjectStatus::PLANNING->canTransitionTo(ProjectStatus::ON_HOLD));
    }
}
