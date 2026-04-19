<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PhaseStatus;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPhase>
 */
class ProjectPhaseFactory extends Factory
{
    protected $model = ProjectPhase::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name_ar' => 'مرحلة '.fake('ar_SA')->word(),
            'name_en' => 'Phase '.fake()->word(),
            'sort_order' => fake()->numberBetween(0, 10),
            'status' => PhaseStatus::PENDING,
            'start_date' => fake()->optional()->date(),
            'end_date' => fake()->optional()->date(),
            'completion_percentage' => 0,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => PhaseStatus::IN_PROGRESS,
            'completion_percentage' => fake()->numberBetween(1, 99),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PhaseStatus::COMPLETED,
            'completion_percentage' => 100,
        ]);
    }
}
