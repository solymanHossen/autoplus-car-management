<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Appointment API Controller
 */
class AppointmentController extends ApiController
{
    /**
     * Display a listing of appointments.
     */
    public function index(): JsonResponse
    {
        try {
            $appointments = QueryBuilder::for(Appointment::class)
                ->allowedFilters(['status', 'customer_id', 'vehicle_id', 'appointment_date'])
                ->allowedSorts(['appointment_date', 'start_time', 'status', 'created_at'])
                ->allowedIncludes(['customer', 'vehicle', 'confirmedBy'])
                ->paginate(15);

            return $this->paginatedResponse(
                $appointments,
                AppointmentResource::class,
                'Appointments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve appointments: '.$e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created appointment.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;

            $appointment = Appointment::create($data);
            $appointment->load(['customer', 'vehicle']);

            return $this->successResponse(
                new AppointmentResource($appointment),
                'Appointment created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create appointment: '.$e->getMessage(), 500);
        }
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->load(['customer', 'vehicle', 'confirmedBy']);

            return $this->successResponse(
                new AppointmentResource($appointment),
                'Appointment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve appointment: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update the specified appointment.
     */
    public function update(StoreAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        try {
            $appointment->update($request->validated());

            return $this->successResponse(
                new AppointmentResource($appointment->fresh()->load(['customer', 'vehicle'])),
                'Appointment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update appointment: '.$e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->delete();

            return $this->successResponse(
                null,
                'Appointment deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete appointment: '.$e->getMessage(), 500);
        }
    }

    /**
     * Confirm an appointment.
     */
    public function confirm(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->update([
                'status' => 'confirmed',
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);

            return $this->successResponse(
                new AppointmentResource($appointment->fresh()->load(['customer', 'vehicle', 'confirmedBy'])),
                'Appointment confirmed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to confirm appointment: '.$e->getMessage(), 500);
        }
    }
}
