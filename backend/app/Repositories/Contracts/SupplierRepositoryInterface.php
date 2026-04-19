<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\SupplierVerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage, ?User $actor = null): LengthAwarePaginator;

    public function findById(int $id): ?SupplierProfile;

    public function findByUserId(int $userId): ?SupplierProfile;

    public function findByCommercialReg(string $commercialReg): ?SupplierProfile;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupplierProfile;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupplierProfile $supplier, array $data): SupplierProfile;

    public function delete(SupplierProfile $supplier): bool;

    public function updateVerificationStatus(
        SupplierProfile $supplier,
        SupplierVerificationStatus $status,
        ?int $verifiedBy
    ): SupplierProfile;
}
