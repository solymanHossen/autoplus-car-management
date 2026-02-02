<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Customer;

test('tenant A cannot see tenant B data', function () {
    config(['tenant.identification_method' => 'header']);
    
    $tenantA = Tenant::factory()->create(['subdomain' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tenant-b']);
    
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAs($userA, 'sanctum')
        ->getJson('/api/v1/customers/' . $customerB->id, [
            'X-Tenant-ID' => $tenantA->id
        ])
        ->assertStatus(404); // Should be hidden via Global Scope OR 403 if ID spoofing is caught
});

test('cross-tenant ID spoofing', function () {
    config(['tenant.identification_method' => 'header']);

    $tenantA = Tenant::factory()->create(['subdomain' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tenant-b']);
    
    // User belongs to Tenant A
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    
    // Create a real token to ensure middleware can see it (simulating real request)
    // actingAs bypasses headers usually, which IdentifyTenant relies on now.
    $token = $userA->createToken('test')->plainTextToken;

    // Attempt to access as Tenant B
    $this->withToken($token)
        ->getJson('/api/v1/customers', [
            'X-Tenant-ID' => $tenantB->id // Spoofing Tenant ID header
        ])
        // With strict TenantScoped users, the user is not found in Tenant B context, 
        // leading to 401 Unauthenticated instead of 403 Forbidden.
        ->assertStatus(401); 
});

test('unauthenticated users cannot see tenant data', function () {
    config(['tenant.identification_method' => 'header']);
    
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    // No auth, but correct tenant header
    $this->getJson('/api/v1/customers', [
            'X-Tenant-ID' => $tenant->id
        ])
        ->assertStatus(401); // Should be unauthorized generally
});
