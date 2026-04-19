<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupplierVerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_pending_supplier_can_be_verified(): void
    {
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/verify")
            ->assertStatus(200);

        $this->assertEquals(
            SupplierVerificationStatus::Verified,
            $supplier->fresh()->verification_status,
        );
        $this->assertNotNull($supplier->fresh()->verified_at);
        $this->assertEquals($this->admin->id, $supplier->fresh()->verified_by);
    }

    public function test_pending_supplier_can_be_suspended(): void
    {
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/suspend")
            ->assertStatus(200);

        $this->assertEquals(
            SupplierVerificationStatus::Suspended,
            $supplier->fresh()->verification_status,
        );
    }

    public function test_verified_supplier_can_be_suspended(): void
    {
        $supplier = SupplierProfile::factory()->verified()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/suspend")
            ->assertStatus(200);

        $this->assertEquals(
            SupplierVerificationStatus::Suspended,
            $supplier->fresh()->verification_status,
        );
    }

    public function test_suspended_supplier_can_be_re_verified(): void
    {
        $supplier = SupplierProfile::factory()->suspended()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/verify")
            ->assertStatus(200);

        $this->assertEquals(
            SupplierVerificationStatus::Verified,
            $supplier->fresh()->verification_status,
        );
    }

    public function test_verify_is_idempotent_for_already_verified_supplier(): void
    {
        $supplier = SupplierProfile::factory()->verified()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/verify")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(
            SupplierVerificationStatus::Verified,
            $supplier->fresh()->verification_status,
        );
    }

    public function test_suspend_is_idempotent_for_already_suspended_supplier(): void
    {
        $supplier = SupplierProfile::factory()->suspended()->create();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}/suspend")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(
            SupplierVerificationStatus::Suspended,
            $supplier->fresh()->verification_status,
        );
    }
}
