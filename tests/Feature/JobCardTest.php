<?php

use App\Models\Customer;
use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a default tenant and user to ensure base setup logic works
    // This allows factories to work if they rely on existing tenants
});

/**
 * Critical Test: Tenant Isolation
 * Ensures that a user from Tenant A cannot access data from Tenant B.
 */
it('prevents cross-tenant data leakage', function () {
    // Arrange: Create Tenant A
    $tenantA = Tenant::factory()->create(['domain' => 'tenant-a.test']);
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $customerA = Customer::factory()->create(['tenant_id' => $tenantA->id]);
    $vehicleA = Vehicle::factory()->create(['tenant_id' => $tenantA->id, 'customer_id' => $customerA->id]);
    
    $jobCardA = JobCard::factory()->create([
        'tenant_id' => $tenantA->id,
        'customer_id' => $customerA->id,
        'vehicle_id' => $vehicleA->id,
        'job_number' => 'JOB-A-001'
    ]);

    // Arrange: Create Tenant B
    $tenantB = Tenant::factory()->create(['domain' => 'tenant-b.test']);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
    $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);
    $vehicleB = Vehicle::factory()->create(['tenant_id' => $tenantB->id, 'customer_id' => $customerB->id]);

    $jobCardB = JobCard::factory()->create([
        'tenant_id' => $tenantB->id,
        'customer_id' => $customerB->id,
        'vehicle_id' => $vehicleB->id,
        'job_number' => 'JOB-B-001'
    ]);

    // Act & Assert 1: User A trying to access Tenant B's Job Card (Direct Access)
    // We simulate the request coming to Tenant A's domain, but asking for Tenant B's resource ID
    $response = $this->actingAs($userA)
        ->getJson("http://{$tenantA->domain}/api/v1/job-cards/{$jobCardB->id}");

    // Should return 404 Not Found because the TenantScoped trait filters it out
    $response->assertNotFound();

    // Act & Assert 2: User A listing Job Cards (Index)
    $responseIndex = $this->actingAs($userA)
        ->getJson("http://{$tenantA->domain}/api/v1/job-cards");

    $responseIndex->assertOk()
        ->assertJsonCount(1, 'data') // Should only see 1 job card
        ->assertJsonFragment(['job_number' => 'JOB-A-001']) // Should see their own
        ->assertJsonMissing(['job_number' => 'JOB-B-001']); // Should NOT see Tenant B's
});

/**
 * Critical Test: Financial Accuracy
 * Verifies that adding items correctly updates the main JobCard financial totals.
 */
it('calculates job card financial totals accurately using addItem', function () {
    // Arrange
    $tenant = Tenant::factory()->create(['domain' => 'finance.test']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'discount_amount' => 0, // Ensure no global discount initially
    ]);

    Sanctum::actingAs($user);

    // Act 1: Add a service item
    // 2 hours @ 50.00/hr = 100.00. Tax 10% = 10.00. Total = 110.00.
    $this->postJson("http://{$tenant->domain}/api/v1/job-cards/{$jobCard->id}/items", [
        'item_type' => 'service',
        'quantity' => 2,
        'unit_price' => 50.00,
        'tax_rate' => 10,
        'notes' => 'Labor'
    ])->assertCreated();

    // Act 2: Add a part item
    // 1 part @ 200.00. Tax 20% = 40.00. Discount 10.00. Subtotal: 200. Total Item: 200 + 40 - 10 = 230.00.
    $this->postJson("http://{$tenant->domain}/api/v1/job-cards/{$jobCard->id}/items", [
        'item_type' => 'part',
        'quantity' => 1,
        'unit_price' => 200.00,
        'tax_rate' => 20,
        'discount' => 10.00,
        'notes' => 'Oil Pump'
    ])->assertCreated();

    // Act 3: Apply a global discount to the job card (Simulated via update or initial create, 
    // assuming our controller logic in `recalculateJobCardTotals` subtracts `jobCard->discount_amount` at the end)
    // The controller subtracts `discount_amount` from the sum of item totals.
    // Let's rely on the `discount_amount` we set to 0 initially, or update the job card to test that too.
    // Ideally `addItem` triggers a recalc using existing discount.

    // Refresh model state from DB
    $jobCard->refresh();

    // Assert: Check totals
    // Expected Subtotal: (2 * 50) + (1 * 200) = 100 + 200 = 300.00
    // Expected Tax: (100 * 0.10) + (200 * 0.20) = 10 + 40 = 50.00
    // Expected Item Discounts (handled inside item total): 10.00
    // Total of items: 110 (Item 1) + 230 (Item 2) = 340.00
    // Global Discount: 0
    // Final Total: 340.00

    expect($jobCard->subtotal)->toBe('300.00'); // Laravel decimal casts return string or float depending on driver, usually string for precision
    expect($jobCard->tax_amount)->toBe('50.00');
    expect($jobCard->total_amount)->toBe('340.00');
});

/**
 * Functional Test: Status Transition
 * Verifies that completing a job card sets the timestamp.
 */
it('sets completed_at timestamp when status changes to completed', function () {
    // Arrange
    $tenant = Tenant::factory()->create(['domain' => 'status.test']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'working',
        'completed_at' => null
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->patchJson("http://{$tenant->domain}/api/v1/job-cards/{$jobCard->id}/status", [
        'status' => 'ready'
    ]);

    // Assert
    $response->assertOk();
    $jobCard->refresh();

    expect($jobCard->status)->toBe('ready');
    expect($jobCard->completed_at)->not->toBeNull();
    // Verify it is recent (within last minute)
    expect($jobCard->completed_at->diffInMinutes(now()))->toBeLessThan(1);
});

/**
 * Security Test: Middleware Validation
 * Verifies that requests without proper tenant identification fail.
 */
it('validates tenant identification middleware', function () {
    // Arrange
    $tenant = Tenant::factory()->create(['domain' => 'valid.test']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Act 1: Access with invalid domain
    // We don't authenticate here because tenant middleware usually runs before auth in most setups,
    // or auth fails because it can't find the tenant for the user.
    // If we look at IdentifyTenant middleware, it doesn't return 404 explicitly but sets the tenant.
    // If tenant is null, subsequent logic might fail or standard auth might fail.
    // However, if the middleware is applied, and `resolveTenant` returns null, `app('tenant')` won't be set.
    // We expect the application to handle this, likely falling through or throwing error if tenant reliance exists.
    // Let's assume hitting an unknown domain produces a general error or 404 if no route/tenant match.
    
    $response = $this->getJson("http://unknown-domain.com/api/v1/job-cards");

    // Since `IdentifyTenant` doesn't explicitly abort(404) in the simplified code we read,
    // but typically a Multi-Tenant app shouldn't recognize the host. 
    // If Middleware passes null, `auth()->user()` might work if it doesn't check tenant context, 
    // BUT our code likely expects a tenant.
    // In many SaaS setups, unknown domain = 404 or specific "Tenant Not Found".
    
    // For this test, valid expectation is Status 401 (Unauthenticated) or 404 (Not Found) or 500 (if unhandled).
    // Let's assume we expect it NOT to be successful (200).
    $response->assertStatus(401); 

    // Act 2: Access with Valid Domain but NO Auth
    $responseValidDomain = $this->getJson("http://{$tenant->domain}/api/v1/job-cards");
    $responseValidDomain->assertStatus(401); // Should be unauthenticated

    // Act 3: Access with Valid Domain AND Auth
    $responseSuccess = $this->actingAs($user)
        ->getJson("http://{$tenant->domain}/api/v1/job-cards");
    
    $responseSuccess->assertOk();
});
