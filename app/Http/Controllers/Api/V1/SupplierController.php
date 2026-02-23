<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SupplierController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $suppliers = QueryBuilder::for(Supplier::class)
            ->allowedFilters(['name', 'email', 'phone', 'city', 'country'])
            ->allowedSorts(['name', 'created_at', 'updated_at'])
            ->paginate($perPage)
            ->appends($request->query());

        return $this->paginatedResponse($suppliers, SupplierResource::class, 'Suppliers retrieved successfully');
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $supplier = Supplier::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return $this->successResponse(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->successResponse(new SupplierResource($supplier), 'Supplier retrieved successfully');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return $this->successResponse(
            new SupplierResource($supplier->fresh()),
            'Supplier updated successfully'
        );
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->successResponse(null, 'Supplier deleted successfully');
    }
}
