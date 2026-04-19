<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SupplierVerificationStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\SupplierService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SupplierServiceTest extends TestCase
{
    private SupplierService $service;

    /** @var MockInterface&SupplierRepositoryInterface */
    private MockInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = Mockery::mock(SupplierRepositoryInterface::class);
        $this->service = new SupplierService($this->repo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ─── create (branch: contractor) ─────────────────────────────────────────

    public function test_contractor_can_create_profile(): void
    {
        $contractor = $this->makeUser(UserRole::CONTRACTOR, id: 42);
        $expected = new SupplierProfile(['id' => 1, 'user_id' => 42]);

        $this->repo->shouldReceive('findByUserId')->with(42)->andReturnNull();
        $this->repo->shouldReceive('findByCommercialReg')->with('CR001')->andReturnNull();
        $this->repo->shouldReceive('create')->once()->andReturn($expected);

        $result = $this->service->create([
            'company_name_ar' => 'شركة',
            'company_name_en' => 'Company',
            'commercial_reg' => 'CR001',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ], $contractor);

        $this->assertSame($expected, $result);
    }

    public function test_admin_without_user_id_throws_validation_error(): void
    {
        $admin = $this->makeUser(UserRole::ADMIN);

        $this->expectException(ApiException::class);

        $this->service->create([
            'company_name_ar' => 'شركة',
            'company_name_en' => 'Company',
            'commercial_reg' => 'CR002',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ], $admin);
    }

    public function test_duplicate_profile_throws_conflict_error(): void
    {
        $contractor = $this->makeUser(UserRole::CONTRACTOR, id: 7);

        $this->repo->shouldReceive('findByUserId')->with(7)->andReturn(new SupplierProfile);

        $this->expectException(ApiException::class);

        $this->service->create([
            'company_name_ar' => 'شركة',
            'company_name_en' => 'Company',
            'commercial_reg' => 'CR003',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ], $contractor);
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_show_visible_supplier_returns_profile(): void
    {
        $supplier = (new SupplierProfile)->forceFill([
            'id' => 5,
            'verification_status' => SupplierVerificationStatus::Verified,
        ]);

        $this->repo->shouldReceive('findById')->with(5)->andReturn($supplier);

        $result = $this->service->show(5, null);

        $this->assertSame($supplier, $result);
    }

    public function test_show_non_visible_supplier_throws_resource_not_found(): void
    {
        $supplier = (new SupplierProfile)->forceFill([
            'id' => 5,
            'user_id' => 99,
            'verification_status' => SupplierVerificationStatus::Pending,
        ]);
        $otherUser = $this->makeUser(UserRole::CONTRACTOR, id: 50);

        $this->repo->shouldReceive('findById')->with(5)->andReturn($supplier);

        $this->expectException(ApiException::class);

        $this->service->show(5, $otherUser);
    }

    public function test_show_nonexistent_supplier_throws_resource_not_found(): void
    {
        $this->repo->shouldReceive('findById')->with(999)->andReturnNull();

        $this->expectException(ApiException::class);

        $this->service->show(999, null);
    }

    // ─── verify / suspend ─────────────────────────────────────────────────────

    public function test_verify_calls_repository_with_verified_status(): void
    {
        $admin = $this->makeUser(UserRole::ADMIN, id: 1);
        $supplier = new SupplierProfile(['id' => 3]);

        $this->repo->shouldReceive('findById')->with(3)->andReturn($supplier);
        $this->repo->shouldReceive('updateVerificationStatus')
            ->with($supplier, SupplierVerificationStatus::Verified, 1)
            ->once()
            ->andReturn($supplier);

        $result = $this->service->verify(3, $admin);

        $this->assertInstanceOf(SupplierProfile::class, $result);
    }

    public function test_suspend_calls_repository_with_suspended_status(): void
    {
        $admin = $this->makeUser(UserRole::ADMIN, id: 2);
        $supplier = new SupplierProfile(['id' => 4]);

        $this->repo->shouldReceive('findById')->with(4)->andReturn($supplier);
        $this->repo->shouldReceive('updateVerificationStatus')
            ->with($supplier, SupplierVerificationStatus::Suspended, null)
            ->once()
            ->andReturn($supplier);

        $result = $this->service->suspend(4, $admin);

        $this->assertInstanceOf(SupplierProfile::class, $result);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_owner_can_update_own_profile(): void
    {
        $owner = $this->makeUser(UserRole::CONTRACTOR, id: 11);
        $supplier = (new SupplierProfile)->forceFill(['id' => 6, 'user_id' => 11]);

        $this->repo->shouldReceive('findById')->with(6)->andReturn($supplier);
        $this->repo->shouldReceive('update')->with($supplier, ['city' => 'جدة'])->once()->andReturn($supplier);

        $result = $this->service->update(6, ['city' => 'جدة'], $owner);

        $this->assertInstanceOf(SupplierProfile::class, $result);
    }

    public function test_non_owner_cannot_update_profile(): void
    {
        $other = $this->makeUser(UserRole::CONTRACTOR, id: 22);
        $supplier = (new SupplierProfile)->forceFill(['id' => 6, 'user_id' => 11]);

        $this->repo->shouldReceive('findById')->with(6)->andReturn($supplier);

        $this->expectException(ApiException::class);

        $this->service->update(6, ['city' => 'جدة'], $other);
    }

    // ─── listProducts stub ────────────────────────────────────────────────────

    public function test_list_products_returns_empty_paginator(): void
    {
        $supplier = (new SupplierProfile)->forceFill(['id' => 7, 'verification_status' => SupplierVerificationStatus::Verified]);

        $this->repo->shouldReceive('findById')->with(7)->andReturn($supplier);

        $result = $this->service->listProducts(7, null, 15);

        $this->assertEquals(0, $result->total());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(UserRole $role, int $id = 1): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;

        $user->shouldReceive('hasEnumRole')
            ->andReturnUsing(fn (UserRole $r) => $r === $role);

        return $user;
    }
}
