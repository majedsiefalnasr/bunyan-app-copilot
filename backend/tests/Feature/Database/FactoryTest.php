<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function admin_factory_attaches_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertEquals('admin', $user->roles->first()?->name);
    }

    #[Test]
    public function customer_factory_attaches_customer_role(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertEquals('customer', $user->roles->first()?->name);
    }

    #[Test]
    public function contractor_factory_attaches_contractor_role(): void
    {
        $user = User::factory()->contractor()->create();

        $this->assertEquals('contractor', $user->roles->first()?->name);
    }

    #[Test]
    public function supervising_architect_factory_attaches_correct_role(): void
    {
        $user = User::factory()->supervisingArchitect()->create();

        $this->assertEquals('supervising_architect', $user->roles->first()?->name);
    }

    #[Test]
    public function field_engineer_factory_attaches_correct_role(): void
    {
        $user = User::factory()->fieldEngineer()->create();

        $this->assertEquals('field_engineer', $user->roles->first()?->name);
    }

    #[Test]
    public function inactive_factory_sets_is_active_false(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse($user->is_active);
    }

    #[Test]
    public function bulk_factory_creates_correct_count_with_role(): void
    {
        $users = User::factory()->count(5)->customer()->create();

        $this->assertCount(5, $users);
        $users->each(function (User $user) {
            $this->assertEquals('customer', $user->roles->first()?->name);
        });
    }
}
