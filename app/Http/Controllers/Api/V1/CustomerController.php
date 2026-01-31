<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Customer API Controller
 */
class CustomerController extends ApiController
{
    /**
     * Display a listing of customers.
     */
    public function index(): JsonResponse
    {
        try {
            $customers = QueryBuilder::for(Customer::class)
                ->allowedFilters(['name', 'email', 'phone', 'city'])
                ->allowedSorts(['name', 'created_at', 'updated_at'])
                ->withCount('vehicles')
                ->paginate(15);

            return $this->paginatedResponse(
                $customers,
                CustomerResource::class,
                'Customers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers: '.$e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = Customer::create(array_merge(
                $request->validated(),
                ['tenant_id' => auth()->user()->tenant_id]
            ));

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create customer: '.$e->getMessage(), 500);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        try {
            $customer->loadCount('vehicles');

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $customer->update($request->validated());

            return $this->successResponse(
                new CustomerResource($customer->fresh()),
                'Customer updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update customer: '.$e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->delete();

            return $this->successResponse(
                null,
                'Customer deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete customer: '.$e->getMessage(), 500);
        }
    }
}
