<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupplierVerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/suppliers';

    // ─── index ───────────────────────────────────────────────────────────────

    public function test_index_is_publicly_accessible(): void
    {
        SupplierProfile::factory()->verified()->create();

        $this->getJson($this->baseUrl)
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_index_returns_only_verified_for_guests(): void
    {
        SupplierProfile::factory()->verified()->create();
        SupplierProfile::factory()->pending()->create();

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_returns_all_statuses_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        SupplierProfile::factory()->verified()->create();
        SupplierProfile::factory()->pending()->create();
        SupplierProfile::factory()->suspended()->create();

        $response = $this->actingAs($admin)
            ->getJson($this->baseUrl);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_show_verified_supplier_is_publicly_accessible(): void
    {
        $supplier = SupplierProfile::factory()->verified()->create();

        $this->getJson("{$this->baseUrl}/{$supplier->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['id' => $supplier->id]]);
    }

    public function test_show_pending_supplier_returns_404_for_guests(): void
    {
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->getJson("{$this->baseUrl}/{$supplier->id}")
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_show_own_pending_profile_returns_200_for_contractor(): void
    {
        $contractor = User::factory()->contractor()->create();
        $supplier = SupplierProfile::factory()->pending()->create(['user_id' => $contractor->id]);

        $this->actingAs($contractor)
            ->getJson("{$this->baseUrl}/{$supplier->id}")
            ->assertStatus(200)
            ->assertJson(['data' => ['id' => $supplier->id]]);
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_contractor_can_create_own_supplier_profile(): void
    {
        $contractor = User::factory()->contractor()->create();

        $response = $this->actingAs($contractor)->postJson($this->baseUrl, [
            'company_name_ar' => 'شركة الاختبار',
            'company_name_en' => 'Test Company',
            'commercial_reg' => 'CR-TEST-001',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['id', 'company_name_ar', 'verification_status']]);

        $this->assertDatabaseHas('supplier_profiles', ['commercial_reg' => 'CR-TEST-001']);
    }

    public function test_admin_can_create_supplier_profile_for_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = User::factory()->contractor()->create();

        $response = $this->actingAs($admin)->postJson($this->baseUrl, [
            'company_name_ar' => 'شركة المسؤول',
            'company_name_en' => 'Admin Company',
            'commercial_reg' => 'CR-ADMIN-001',
            'phone' => '0598765432',
            'city' => 'جدة',
            'user_id' => $contractor->id,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_admin_store_without_user_id_returns_validation_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson($this->baseUrl, [
            'company_name_ar' => 'شركة',
            'company_name_en' => 'Company',
            'commercial_reg' => 'CR-NOID-001',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ])->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_duplicate_commercial_reg_returns_conflict(): void
    {
        $contractor1 = User::factory()->contractor()->create();
        $contractor2 = User::factory()->contractor()->create();
        SupplierProfile::factory()->verified()->create([
            'user_id' => $contractor1->id,
            'commercial_reg' => 'CR-DUP-001',
        ]);

        $this->actingAs($contractor2)->postJson($this->baseUrl, [
            'company_name_ar' => 'شركة جديدة',
            'company_name_en' => 'New Company',
            'commercial_reg' => 'CR-DUP-001',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ])->assertStatus(409)
            ->assertJson(['success' => false, 'error' => ['code' => 'CONFLICT_ERROR']]);
    }

    public function test_unauthorized_role_cannot_store(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->postJson($this->baseUrl, [
            'company_name_ar' => 'شركة',
            'company_name_en' => 'Company',
            'commercial_reg' => 'CR-UNAUTH-001',
            'phone' => '0512345678',
            'city' => 'الرياض',
        ])->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_contractor_can_update_own_profile(): void
    {
        $contractor = User::factory()->contractor()->create();
        $supplier = SupplierProfile::factory()->create(['user_id' => $contractor->id]);

        $this->actingAs($contractor)->putJson("{$this->baseUrl}/{$supplier->id}", [
            'city' => 'مكة المكرمة',
        ])->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('supplier_profiles', ['id' => $supplier->id, 'city' => 'مكة المكرمة']);
    }

    public function test_contractor_cannot_update_other_supplier_profile(): void
    {
        $contractor = User::factory()->contractor()->create();
        $other = User::factory()->contractor()->create();
        $supplier = SupplierProfile::factory()->create(['user_id' => $other->id]);

        $this->actingAs($contractor)->putJson("{$this->baseUrl}/{$supplier->id}", [
            'city' => 'مكة المكرمة',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_any_supplier_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = SupplierProfile::factory()->create();

        $this->actingAs($admin)->putJson("{$this->baseUrl}/{$supplier->id}", [
            'city' => 'الدمام',
        ])->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ─── verify ───────────────────────────────────────────────────────────────

    public function test_admin_can_verify_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->actingAs($admin)->putJson("{$this->baseUrl}/{$supplier->id}/verify")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('supplier_profiles', [
            'id' => $supplier->id,
            'verification_status' => SupplierVerificationStatus::Verified->value,
        ]);
    }

    public function test_contractor_cannot_verify_supplier(): void
    {
        $contractor = User::factory()->contractor()->create();
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->actingAs($contractor)->putJson("{$this->baseUrl}/{$supplier->id}/verify")
            ->assertStatus(403);
    }

    // ─── suspend ──────────────────────────────────────────────────────────────

    public function test_admin_can_suspend_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = SupplierProfile::factory()->verified()->create();

        $this->actingAs($admin)->putJson("{$this->baseUrl}/{$supplier->id}/suspend")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('supplier_profiles', [
            'id' => $supplier->id,
            'verification_status' => SupplierVerificationStatus::Suspended->value,
        ]);
    }

    public function test_contractor_cannot_suspend_supplier(): void
    {
        $contractor = User::factory()->contractor()->create();
        $supplier = SupplierProfile::factory()->pending()->create();

        $this->actingAs($contractor)->putJson("{$this->baseUrl}/{$supplier->id}/suspend")
            ->assertStatus(403);
    }

    // ─── products ─────────────────────────────────────────────────────────────

    public function test_products_endpoint_accessible_for_verified_supplier(): void
    {
        $supplier = SupplierProfile::factory()->verified()->create();

        $this->getJson("{$this->baseUrl}/{$supplier->id}/products")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
