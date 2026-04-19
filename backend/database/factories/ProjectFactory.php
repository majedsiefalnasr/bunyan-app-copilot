<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 month', '+6 months');
        $endDate = fake()->dateTimeBetween($startDate, '+2 years');

        return [
            'owner_id' => User::factory()->customer(),
            'name_ar' => 'مشروع '.fake('ar_SA')->word(),
            'name_en' => 'Project '.fake()->word(),
            'description' => fake()->optional()->paragraph(),
            'city' => fake()->randomElement(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة']),
            'district' => fake()->optional()->word(),
            'location_lat' => fake()->optional()->latitude(17, 32),
            'location_lng' => fake()->optional()->longitude(36, 56),
            'status' => ProjectStatus::DRAFT,
            'type' => ProjectType::RESIDENTIAL,
            'budget_estimated' => fake()->randomFloat(2, 100000, 10000000),
            'budget_actual' => 0.00,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function planning(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::PLANNING]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::IN_PROGRESS]);
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::ON_HOLD]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::COMPLETED]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::CLOSED]);
    }

    public function commercial(): static
    {
        return $this->state(fn () => ['type' => ProjectType::COMMERCIAL]);
    }

    public function infrastructure(): static
    {
        return $this->state(fn () => ['type' => ProjectType::INFRASTRUCTURE]);
    }
}
