<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_has_soft_deletes_trait(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(User::class),
        );
    }

    #[Test]
    public function scope_active_filters_inactive_users(): void
    {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => true]);
        User::factory()->inactive()->create();

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
    }

    #[Test]
    public function has_role_string_returns_true_for_attached_role(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'display_name_ar' => 'الإدارة',
        ]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->load('roles');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('customer'));
    }

    #[Test]
    public function has_enum_role_returns_correct_bool(): void
    {
        $user = User::factory()->create();
        $user->role = UserRole::CUSTOMER;
        $user->save();

        $this->assertTrue($user->hasEnumRole(UserRole::CUSTOMER));
        $this->assertFalse($user->hasEnumRole(UserRole::ADMIN));
    }

    #[Test]
    public function fillable_includes_phone_is_active_avatar(): void
    {
        $user = new User;
        $fillable = $user->getFillable();

        $this->assertContains('phone', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('avatar', $fillable);
    }

    #[Test]
    public function fillable_does_not_include_role(): void
    {
        $user = new User;

        $this->assertNotContains('role', $user->getFillable());
    }

    #[Test]
    public function password_is_stored_as_bcrypt_hash(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->assertTrue(Hash::check('password', $user->password));
    }
}
