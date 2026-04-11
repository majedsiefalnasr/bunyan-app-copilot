<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function role_seeder_creates_exactly_five_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 5);
    }

    #[Test]
    public function role_seeder_creates_all_correct_slugs(): void
    {
        $this->seed(RoleSeeder::class);

        $expectedSlugs = ['admin', 'customer', 'contractor', 'supervising_architect', 'field_engineer'];

        foreach ($expectedSlugs as $slug) {
            $this->assertDatabaseHas('roles', ['name' => $slug]);
        }
    }

    #[Test]
    public function roles_have_correct_arabic_display_names(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin', 'display_name_ar' => 'الإدارة']);
        $this->assertDatabaseHas('roles', ['name' => 'customer', 'display_name_ar' => 'العميل']);
        $this->assertDatabaseHas('roles', ['name' => 'contractor', 'display_name_ar' => 'المقاول']);
        $this->assertDatabaseHas('roles', ['name' => 'supervising_architect', 'display_name_ar' => 'المهندس المشرف']);
        $this->assertDatabaseHas('roles', ['name' => 'field_engineer', 'display_name_ar' => 'المهندس الميداني']);
    }

    #[Test]
    public function role_seeder_is_idempotent(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 5);
    }
}
