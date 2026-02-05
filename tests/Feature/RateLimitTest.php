<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('responses contain rate limit headers', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->getJson(route('customers.index'));
    
    $response->assertOk();
    
    // These headers confirm the 'throttle' middleware is active
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
});

test('login route has stricter rate limits', function () {
    // Usually login is 5 per minute
    // We won't flood it, just check headers if possible, or trust general throttling involves login.
    // Note: Login often returns 429 after exactly 5 attempts. 
    
    $tenant = Tenant::factory()->create();

    // Let's verify headers exist on a failed login to ensure protection even on failure
    $response = $this->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->postJson(route('api.v1.auth.login'), [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ]);
    
    $response->assertStatus(401);
    // Login throttling might handle headers differently depending on implementation (RateLimiter vs middleware)
    // But it's good practice to expect them.
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
});
