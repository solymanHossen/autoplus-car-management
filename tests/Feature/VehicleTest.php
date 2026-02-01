<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Functional Test: Vehicle CRUD
 */
it('allows a user to manage vehicles within their tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    // Create
    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/vehicles', [
            'customer_id' => $customer->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'registration_number' => 'ABC-123',
            'vin' => '1234567890ABCDEF',
            'current_mileage' => 50000
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['make' => 'Toyota']);

    $vehicleId = $response->json('data.id');

    // Read (Index)
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson('/api/v1/vehicles')
        ->assertOk()
        ->assertJsonFragment(['id' => $vehicleId]);

    // Update
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->putJson("/api/v1/vehicles/{$vehicleId}", [
            'make' => 'Toyota',
            'model' => 'Camry', // Changed
            'year' => 2021,
            'registration_number' => 'ABC-123',
            'customer_id' => $customer->id
        ])
        ->assertOk()
        ->assertJsonFragment(['model' => 'Camry']);

    // Delete
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->deleteJson("/api/v1/vehicles/{$vehicleId}")
        ->assertOk();

    $this->assertSoftDeleted('vehicles', ['id' => $vehicleId]);
});

/**
 * Security: Cross-Tenant Isolation
 */
it('prevents managing vehicles of other tenants', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    
    $tenantB = Tenant::factory()->create();
    $vehicleB = Vehicle::factory()->create(['tenant_id' => $tenantB->id]);

    Sanctum::actingAs($userA);

    $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->getJson("/api/v1/vehicles/{$vehicleB->id}");

    $response->assertNotFound();
});

/**
 * Validation: Required Fields
 */
it('validates vehicle input', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/vehicles', [
            'make' => '', // Empty
        ]);

    $response->assertJsonValidationErrors(['make', 'customer_id', 'registration_number']);
});
