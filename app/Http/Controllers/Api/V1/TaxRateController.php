<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TaxRateController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $taxRates = QueryBuilder::for(TaxRate::class)
            ->allowedFilters(['name', 'is_active'])
            ->allowedSorts(['name', 'rate', 'created_at'])
            ->paginate($perPage)
            ->appends($request->query());

        return $this->paginatedResponse($taxRates, TaxRateResource::class, 'Tax rates retrieved successfully');
    }

    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $taxRate = TaxRate::create(array_merge(
            $request->validated(),
            [
                'tenant_id' => $tenantId,
                'is_active' => $request->validated()['is_active'] ?? true,
            ]
        ));

        return $this->successResponse(new TaxRateResource($taxRate), 'Tax rate created successfully', 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return $this->successResponse(new TaxRateResource($taxRate), 'Tax rate retrieved successfully');
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $taxRate->update($request->validated());

        return $this->successResponse(
            new TaxRateResource($taxRate->fresh()),
            'Tax rate updated successfully'
        );
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();

        return $this->successResponse(null, 'Tax rate deleted successfully');
    }
}
