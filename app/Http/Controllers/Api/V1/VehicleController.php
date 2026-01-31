<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Vehicle API Controller
 */
class VehicleController extends ApiController
{
    /**
     * Display a listing of vehicles.
     */
    public function index(): JsonResponse
    {
        try {
            $vehicles = QueryBuilder::for(Vehicle::class)
                ->allowedFilters(['registration_number', 'make', 'model', 'customer_id'])
                ->allowedSorts(['make', 'model', 'year', 'created_at'])
                ->allowedIncludes(['customer'])
                ->paginate(15);

            return $this->paginatedResponse(
                $vehicles,
                VehicleResource::class,
                'Vehicles retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve vehicles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::create(array_merge(
                $request->validated(),
                ['tenant_id' => auth()->user()->tenant_id]
            ));

            $vehicle->load('customer');

            return $this->successResponse(
                new VehicleResource($vehicle),
                'Vehicle created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create vehicle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        try {
            $vehicle->load('customer');
            
            return $this->successResponse(
                new VehicleResource($vehicle),
                'Vehicle retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve vehicle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified vehicle.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $vehicle->update($request->validated());

            return $this->successResponse(
                new VehicleResource($vehicle->fresh()->load('customer')),
                'Vehicle updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update vehicle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        try {
            $vehicle->delete();

            return $this->successResponse(
                null,
                'Vehicle deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete vehicle: ' . $e->getMessage(), 500);
        }
    }
}
