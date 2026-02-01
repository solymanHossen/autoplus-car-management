<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Functional Test: Customer CRUD
 */
it('allows a user to manage customers within their tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    // Create
    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Street',
            'city' => 'Metropolis'
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'John Doe']);

    $customerId = $response->json('data.id');

    // Read (Index)
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonFragment(['id' => $customerId]);

    // Update
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->putJson("/api/v1/customers/{$customerId}", [
            'name' => 'Johnathan Doe',
            'email' => 'john@example.com'
        ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Johnathan Doe']);

    // Delete
    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->deleteJson("/api/v1/customers/{$customerId}")
        ->assertOk();

    $this->assertSoftDeleted('customers', ['id' => $customerId]);
});

/**
 * Security: Cross-Tenant Isolation
 */
it('hides customers from other tenants', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $customerA = Customer::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Customer A']);

    $tenantB = Tenant::factory()->create();
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
    $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Customer B']);

    Sanctum::actingAs($userA);

    // List: Should only see Customer A
    $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Customer A'])
        ->assertJsonMissing(['name' => 'Customer B']);

    // View: Accessing Customer B directly should fail
    $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->getJson("/api/v1/customers/{$customerB->id}")
        ->assertNotFound();
});

/**
 * Validation: Required Fields
 */
it('requires valid data to create a customer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/customers', [
            'name' => '', // Empty
            'phone' => ''
        ]);

    $response->assertJsonValidationErrors(['name', 'phone']);
});
