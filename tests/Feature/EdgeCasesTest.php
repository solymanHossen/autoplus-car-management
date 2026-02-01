<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Data provider for ALL protected GET routes.
 */
dataset('protected_get_routes', [
    'customers.index' => ['/api/v1/customers'],
    'vehicles.index' => ['/api/v1/vehicles'],
    'invoices.index' => ['/api/v1/invoices'],
    'payments.index' => ['/api/v1/payments'],
    'job-cards.index' => ['/api/v1/job-cards'],
    'appointments.index' => ['/api/v1/appointments'],
    'auth.me' => ['/api/v1/auth/me'],
]);

/**
 * Data provider for resource Detail routes (need non-existent ID).
 */
dataset('resource_detail_routes', [
    'customers.show' => ['/api/v1/customers/999999'],
    'vehicles.show' => ['/api/v1/vehicles/999999'],
    'invoices.show' => ['/api/v1/invoices/999999'],
    'payments.show' => ['/api/v1/payments/999999'],
    'job-cards.show' => ['/api/v1/job-cards/999999'],
    'appointments.show' => ['/api/v1/appointments/999999'],
]);

/**
 * Data provider for POST routes (validation check).
 */
dataset('post_routes', [
    'customers.store' => ['/api/v1/customers'],
    'vehicles.store' => ['/api/v1/vehicles'],
    'invoices.store' => ['/api/v1/invoices'],
    'payments.store' => ['/api/v1/payments'],
    'job-cards.store' => ['/api/v1/job-cards'],
    'appointments.store' => ['/api/v1/appointments'],
]);

/**
 * Negative Test 1: Unauthenticated Access
 * Loop through all routes and ensure guests get 401.
 */
it('rejects unauthenticated access to protected routes', function (string $route) {
    // We do NOT use actingAs here.
    // However, IdentifyTenant middleware might run first.
    // If we don't provide a tenant, it might fail or pass depending on config.
    // But Auth middleware checks for token.
    // We expect 401 JSON.

    $response = $this->getJson($route);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'error_code' => 'UNAUTHENTICATED'
        ]);
})->with('protected_get_routes');

/**
 * Negative Test 2: Resource Not Found (Global 404)
 * Ensures API returns consistent 404 structure for invalid resource IDs.
 */
it('returns 404 for non-existent resources', function (string $route) {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson($route);

    // Note: Laravel Model binding typically throws 404.
    // Our Exception Handler in bootstrap/app.php guarantees JSON.
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'error_code' => 'NOT_FOUND'
        ]);
})->with('resource_detail_routes');

/**
 * Negative Test 3: Validation Failure (Global 422)
 * Ensures API returns consistent validation error structure.
 */
it('returns 422 for empty payloads on creation', function (string $route) {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson($route, []); // Empty payload

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
            'error_code'
        ]);
    
    expect($response->json('message'))->toBe('Validation failed.');
    expect($response->json('error_code'))->toBe('VALIDATION_ERROR');
})->with('post_routes');

/**
 * Negative Test 4: Invalid Methods (Method Not Allowed)
 * Try POSTing to a GET only route (e.g. index) if strict, or PUT to index.
 * Laravel handles this with 405.
 */
it('rejects invalid HTTP methods', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    // Try POST to a resource detail endpoint (usually GET/PUT/DELETE)
    // Actually POST to /api/v1/customers/123 is usually not defined
    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson('/api/v1/customers/1');

    // 405 Method Not Allowed
    // Verify our Exception Handler doesn't crash on this
    // We didn't explicitly customize 405 in bootstrap/app.php, so it might be default HTML or JSON?
    // Let's check status code first.
    $response->assertStatus(405);
});
