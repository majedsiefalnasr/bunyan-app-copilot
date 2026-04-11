<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(new User);
    }

    #[Test]
    public function find_all_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $users = $this->repository->findAll();

        $this->assertCount(3, $users);
    }

    #[Test]
    public function find_returns_correct_user(): void
    {
        $user = User::factory()->create();

        $found = $this->repository->find($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function find_by_email_returns_matching_user(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $found = $this->repository->findBy(['email' => 'test@example.com']);

        $this->assertCount(1, $found);
        $this->assertEquals($user->id, $found->first()->id);
    }

    #[Test]
    public function find_by_email_method_returns_correct_user(): void
    {
        $user = User::factory()->create(['email' => 'specific@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        $found = $this->repository->findByEmail('specific@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function find_by_email_method_returns_null_for_unknown_email(): void
    {
        $found = $this->repository->findByEmail('nobody@example.com');

        $this->assertNull($found);
    }

    #[Test]
    public function create_persists_user_to_database(): void
    {
        $data = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
        ];

        $user = $this->repository->create($data);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertEquals('New User', $user->name);
    }

    #[Test]
    public function update_modifies_user_attributes(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $updated = $this->repository->update($user->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function delete_soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $this->repository->delete($user->id);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::find($user->id));
    }

    #[Test]
    public function find_active_users_excludes_inactive(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->inactive()->create();

        $activeUsers = $this->repository->findActiveUsers();

        $this->assertCount(3, $activeUsers);
        $this->assertTrue($activeUsers->every(fn ($u) => $u->is_active === true));
    }

    #[Test]
    public function paginate_returns_length_aware_paginator_with_correct_per_page(): void
    {
        User::factory()->count(20)->create();

        $paginator = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(20, $paginator->total());
    }
}
