<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\SupplierVerificationStatus;
use App\Enums\UserRole;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository implements SupplierRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage, ?User $actor = null): LengthAwarePaginator
    {
        return SupplierProfile::query()
            ->visibleTo($actor)
            ->when(isset($filters['city']), fn ($q) => $q->byCity($filters['city']))
            ->when(isset($filters['district']), fn ($q) => $q->where('district', $filters['district']))
            ->when(isset($filters['search']), fn ($q) => $q->search($filters['search']))
            ->when(
                isset($filters['verification_status']) && $actor !== null && $actor->hasEnumRole(UserRole::ADMIN),
                fn ($q) => $q->where('verification_status', $filters['verification_status'])
            )
            ->paginate($perPage);
    }

    public function findById(int $id): ?SupplierProfile
    {
        return SupplierProfile::find($id);
    }

    public function findByUserId(int $userId): ?SupplierProfile
    {
        return SupplierProfile::where('user_id', $userId)->first();
    }

    public function findByCommercialReg(string $commercialReg): ?SupplierProfile
    {
        return SupplierProfile::where('commercial_reg', $commercialReg)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupplierProfile
    {
        return SupplierProfile::forceCreate($data);
    }

    /**
     * Update a supplier profile instance directly (avoids double-fetch).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SupplierProfile $supplier, array $data): SupplierProfile
    {
        $supplier->update($data);

        return $supplier->fresh() ?? $supplier;
    }

    /**
     * Soft-delete a supplier profile instance directly.
     */
    public function delete(SupplierProfile $supplier): bool
    {
        return (bool) $supplier->delete();
    }

    public function updateVerificationStatus(
        SupplierProfile $supplier,
        SupplierVerificationStatus $status,
        ?int $verifiedBy
    ): SupplierProfile {
        $supplier->verification_status = $status;
        $supplier->verified_by = $verifiedBy;
        $supplier->verified_at = $status === SupplierVerificationStatus::Verified ? now() : $supplier->verified_at;
        $supplier->save();

        return $supplier;
    }
}
