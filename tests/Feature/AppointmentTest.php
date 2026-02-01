<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Functional Test: Scheduling
 */
it('can schedule an appointment', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00', // Assuming migration uses time or datetime
            'service_type' => 'Service A',
            'status' => 'pending'
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('appointments', [
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'status' => 'pending'
    ]);
});

/**
 * Logic: Confirmation
 */
it('allows confirming an appointment', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id, 
        'status' => 'pending'
    ]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson("/api/v1/appointments/{$appointment->id}/confirm");

    $response->assertOk();

    $appointment->refresh();
    expect($appointment->status)->toBe('confirmed');
    expect($appointment->confirmed_by)->toBe($user->id);
    expect($appointment->confirmed_at)->not->toBeNull();
});

/**
 * Validation: Logic
 */
it('validates appointment dates', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    // Try to book in the past (if validation exists)
    // Or check simple required fields
    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/appointments', [
            'appointment_date' => '', // Empty
        ]);

    $response->assertJsonValidationErrors(['appointment_date']);
});

/**
 * Security: Isolation
 */
it('prevents managing appointments of other tenants', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $appointmentB = Appointment::factory()->create(['tenant_id' => $tenantB->id]);

    Sanctum::actingAs($userA);

    $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->getJson("/api/v1/appointments/{$appointmentB->id}");

    $response->assertNotFound();
});
