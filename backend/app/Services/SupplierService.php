<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Enums\SupplierVerificationStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, ?User $actor): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->supplierRepository->paginate($filters, $perPage, $actor);
    }

    /**
     * Returns the supplier if visible to the actor, or throws RESOURCE_NOT_FOUND.
     *
     * ADR-009-01: 404 (not 403) is returned for non-visible profiles to prevent
     * existence enumeration — callers cannot distinguish "not found" from "exists
     * but not accessible".
     */
    public function show(int $id, ?User $actor): SupplierProfile
    {
        $supplier = $this->supplierRepository->findById($id);

        if ($supplier === null) {
            throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));
        }

        if (! $this->isVisibleTo($supplier, $actor)) {
            throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));
        }

        return $supplier;
    }

    /**
     * Create a supplier profile.
     *
     * Three-branch logic:
     * 1. Admin + user_id provided → verify target has contractor role, use target id
     * 2. Admin + no user_id → VALIDATION_ERROR (admin must specify a contractor)
     * 3. Contractor → always use $actor->id regardless of any user_id in data
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SupplierProfile
    {
        if ($actor->hasEnumRole(UserRole::ADMIN)) {
            if (empty($data['user_id'])) {
                throw ApiException::make(
                    ApiErrorCode::VALIDATION_ERROR,
                    trans('suppliers.role_required'),
                    ['user_id' => [trans('suppliers.validation.user_id_required_for_admin')]]
                );
            }

            $targetUser = User::find((int) $data['user_id']);

            if ($targetUser === null || ! $targetUser->hasEnumRole(UserRole::CONTRACTOR)) {
                throw ApiException::make(
                    ApiErrorCode::RBAC_ROLE_DENIED,
                    trans('suppliers.role_required')
                );
            }

            $ownerId = (int) $data['user_id'];
        } else {
            // Contractor: always own profile, ignore any user_id in data
            $ownerId = $actor->id;
        }

        if ($this->supplierRepository->findByUserId($ownerId) !== null) {
            throw ApiException::make(ApiErrorCode::CONFLICT_ERROR, trans('suppliers.already_exists'));
        }

        if (isset($data['commercial_reg']) && $this->supplierRepository->findByCommercialReg($data['commercial_reg']) !== null) {
            throw ApiException::make(ApiErrorCode::CONFLICT_ERROR, trans('suppliers.commercial_reg_taken'));
        }

        $profileData = $data;
        $profileData['user_id'] = $ownerId;
        $profileData['verification_status'] = SupplierVerificationStatus::Pending->value;

        return $this->supplierRepository->create($profileData);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $actor): SupplierProfile
    {
        $supplier = $this->supplierRepository->findById($id);

        if ($supplier === null) {
            throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));
        }

        if (! $actor->hasEnumRole(UserRole::ADMIN) && $supplier->user_id !== $actor->id) {
            throw ApiException::make(ApiErrorCode::AUTH_UNAUTHORIZED, trans('suppliers.unauthorized_update'));
        }

        return $this->supplierRepository->update($supplier, $data);
    }

    /**
     * Verify a supplier (admin only). Idempotent.
     */
    public function verify(int $id, User $admin): SupplierProfile
    {
        $supplier = $this->supplierRepository->findById($id);

        if ($supplier === null) {
            throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));
        }

        return $this->supplierRepository->updateVerificationStatus(
            $supplier,
            SupplierVerificationStatus::Verified,
            $admin->id
        );
    }

    /**
     * Suspend a supplier (admin only). Idempotent.
     */
    public function suspend(int $id, User $admin): SupplierProfile
    {
        $supplier = $this->supplierRepository->findById($id);

        if ($supplier === null) {
            throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));
        }

        return $this->supplierRepository->updateVerificationStatus(
            $supplier,
            SupplierVerificationStatus::Suspended,
            null
        );
    }

    /**
     * List products for a supplier — stub until STAGE_08.
     *
     * ADR-SUPPLIER-04: Returns empty paginator stub.
     */
    public function listProducts(int $id, ?User $actor, int $perPage = 15): LengthAwarePaginator
    {
        $supplier = $this->show($id, $actor);

        /** @var array<int, mixed> $emptyItems */
        $emptyItems = [];

        return new ConcretePaginator($emptyItems, 0, $perPage, 1);
    }

    /**
     * Aggregate ratings — no-op stub until the reviews stage.
     */
    public function aggregateRatings(int $supplierId): void
    {
        // No-op stub — implemented in the reviews stage
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function isVisibleTo(SupplierProfile $supplier, ?User $actor): bool
    {
        if ($actor !== null && $actor->hasEnumRole(UserRole::ADMIN)) {
            return true;
        }

        if ($supplier->verification_status === SupplierVerificationStatus::Verified) {
            return true;
        }

        if ($actor !== null && $supplier->user_id === $actor->id) {
            return true;
        }

        return false;
    }
}
