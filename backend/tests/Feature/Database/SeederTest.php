<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function permission_seeder_creates_at_least_25_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertGreaterThanOrEqual(25, Permission::count());
    }

    #[Test]
    public function each_permission_has_non_empty_group(): void
    {
        $this->seed(PermissionSeeder::class);

        $emptyGroups = Permission::where('group', '')->orWhereNull('group')->count();
        $this->assertEquals(0, $emptyGroups);
    }

    #[Test]
    public function user_seeder_creates_five_test_users(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        $this->assertDatabaseCount('users', 5);
    }

    #[Test]
    public function each_test_user_has_exactly_one_role(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        User::all()->each(function (User $user) {
            $this->assertCount(1, $user->roles);
        });
    }

    #[Test]
    public function user_seeder_creates_expected_emails(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        $expectedEmails = [
            'admin@bunyan.test',
            'customer@bunyan.test',
            'contractor@bunyan.test',
            'architect@bunyan.test',
            'engineer@bunyan.test',
        ];

        foreach ($expectedEmails as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }

    #[Test]
    public function database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 5);
    }
}
