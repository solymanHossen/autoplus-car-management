<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $products = QueryBuilder::for(Product::class)
            ->allowedFilters(['sku', 'name', 'type', 'category', 'supplier_id'])
            ->allowedSorts(['name', 'sku', 'unit_price', 'stock_quantity', 'created_at'])
            ->allowedIncludes(['supplier'])
            ->paginate($perPage)
            ->appends($request->query());

        return $this->paginatedResponse($products, ProductResource::class, 'Products retrieved successfully');
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $product = Product::create(array_merge(
            $request->validated(),
            [
                'tenant_id' => $tenantId,
                'stock_quantity' => $request->validated()['stock_quantity'] ?? 0,
                'min_stock_level' => $request->validated()['min_stock_level'] ?? 0,
            ]
        ));

        return $this->successResponse(
            new ProductResource($product->load('supplier')),
            'Product created successfully',
            201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse(
            new ProductResource($product->load('supplier')),
            'Product retrieved successfully'
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return $this->successResponse(
            new ProductResource($product->fresh()->load('supplier')),
            'Product updated successfully'
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
