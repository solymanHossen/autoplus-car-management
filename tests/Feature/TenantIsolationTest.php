<?php

use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user cannot view job cards from another tenant', function () {
    // Create two tenants
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Create a user for Tenant A
    $userA = User::factory()->create([
        'tenant_id' => $tenantA->id,
    ]);

    // Create a user for Tenant B
    $userB = User::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    // Create a JobCard for Tenant A
    $jobCardA = JobCard::factory()->create([
        'tenant_id' => $tenantA->id,
    ]);

    // Authenticate as User B (Tenant B)
    $this->actingAs($userB);

    // Attempt to access JobCard A via API show endpoint
    $response = $this->getJson(route('job-cards.show', $jobCardA));

    // Assert 404 Not Found (Standard for Model Not Found or Scoped queries)
    $response->assertNotFound();
});

test('a user only sees job cards from their own tenant in index', function () {
    // Create two tenants
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Create a user for Tenant A
    $userA = User::factory()->create([
        'tenant_id' => $tenantA->id,
    ]);

    // Create JobCards for Tenant A
    JobCard::factory()->count(3)->create([
        'tenant_id' => $tenantA->id,
    ]);

    // Create a JobCard for Tenant B
    JobCard::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    // Authenticate as User A
    $this->actingAs($userA);

    // Get index
    $response = $this->getJson(route('job-cards.index'));

    $response->assertOk();
    
    // Assert we see 3 items (from Tenant A) and not the one from Tenant B
    $response->assertJsonCount(3, 'data');
});
