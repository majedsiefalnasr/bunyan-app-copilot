<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\SuspendSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Requests\Supplier\VerifySupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\SupplierProfile;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseApiController
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupplierProfile::class);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $filters = array_merge($request->only(['city', 'district', 'search', 'verification_status']), [
            'per_page' => $perPage,
        ]);

        $paginator = $this->supplierService->list($filters, $request->user());

        return $this->paginated(SupplierResource::collection($paginator), $paginator);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // Authorization skipped — service enforces visibility and throws
        // RESOURCE_NOT_FOUND (404) for non-visible profiles (ADR-009-01).
        $result = $this->supplierService->show($id, $request->user());

        return $this->success(new SupplierResource($result));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', SupplierProfile::class);

        $result = $this->supplierService->create($request->validated(), $request->user());

        return $this->success(new SupplierResource($result), null, 201);
    }

    public function update(UpdateSupplierRequest $request, SupplierProfile $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $result = $this->supplierService->update($supplier->id, $request->validated(), $request->user());

        return $this->success(new SupplierResource($result));
    }

    public function verify(VerifySupplierRequest $request, SupplierProfile $supplier): JsonResponse
    {
        $this->authorize('verify', SupplierProfile::class);

        $result = $this->supplierService->verify($supplier->id, $request->user());

        return $this->success(new SupplierResource($result));
    }

    public function suspend(SuspendSupplierRequest $request, SupplierProfile $supplier): JsonResponse
    {
        $this->authorize('suspend', SupplierProfile::class);

        $result = $this->supplierService->suspend($supplier->id, $request->user());

        return $this->success(new SupplierResource($result));
    }

    public function products(Request $request, SupplierProfile $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->supplierService->listProducts($supplier->id, $request->user(), $perPage);

        return $this->paginated([], $paginator);
    }
}
