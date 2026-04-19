<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupplierVerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProfile>
 */
class SupplierProfileFactory extends Factory
{
    protected $model = SupplierProfile::class;

    /**
     * Default state: pending supplier with an owning contractor user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->contractor(),
            'company_name_ar' => fake()->company().' للمقاولات',
            'company_name_en' => fake()->company().' Contracting',
            'commercial_reg' => fake()->unique()->numerify('##########'),
            'tax_number' => fake()->optional(0.7)->numerify('3##########3'),
            'city' => fake()->randomElement(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة']),
            'district' => fake()->optional()->streetName(),
            'address' => fake()->optional()->address(),
            'phone' => '05'.fake()->numerify('########'),
            'verification_status' => SupplierVerificationStatus::Pending->value,
            'verified_at' => null,
            'verified_by' => null,
            'rating_avg' => 0.00,
            'total_ratings' => 0,
            'description_ar' => fake()->optional()->paragraph(),
            'description_en' => fake()->optional()->paragraph(),
            'logo' => fake()->optional()->imageUrl(200, 200, 'business'),
            'website' => fake()->optional()->url(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => SupplierVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => SupplierVerificationStatus::Pending->value,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => SupplierVerificationStatus::Suspended->value,
        ]);
    }
}
