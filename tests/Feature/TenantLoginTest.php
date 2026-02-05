<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users with same email can login identifying correct tenant', function () {
    // 1. Setup Tenants
    $tenantA = Tenant::factory()->create(['domain' => 'tenant-a.local']);
    $tenantB = Tenant::factory()->create(['domain' => 'tenant-b.local']);

    // 2. Setup Users with same email
    $userA = User::factory()->create([
        'name' => 'User A',
        'email' => 'shared@example.com',
        'tenant_id' => $tenantA->id,
        'password' => Hash::make('password-a'),
        'role' => 'owner'
    ]);

    $userB = User::factory()->create([
        'name' => 'User B',
        'email' => 'shared@example.com',
        'tenant_id' => $tenantB->id,
        'password' => Hash::make('password-b'),
        'role' => 'owner'
    ]);

    // 3. Test Login Tenant A
    $responseA = $this->withHeaders(['X-Tenant-ID' => $tenantA->id])
        ->postJson('/api/v1/auth/login', [
            'email' => 'shared@example.com',
            'password' => 'password-a',
        ]);

    $responseA->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);

    // 4. Test Login Tenant B
    $responseB = $this->withHeaders(['X-Tenant-ID' => $tenantB->id])
        ->postJson('/api/v1/auth/login', [
             'email' => 'shared@example.com',
             'password' => 'password-b',
        ]);

    $responseB->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);
});

test('login fails if tenant id is wrong (cross tenant attack)', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create([
        'tenant_id' => $tenantA->id,
        'email' => 'victim@example.com',
        'password' => Hash::make('secret'),
    ]);

    // Attacker tries to login to Tenant B using Tenant A's user creds
    $response = $this->withHeaders(['X-Tenant-ID' => $tenantB->id])
        ->postJson('/api/v1/auth/login', [
            'email' => 'victim@example.com',
            'password' => 'secret',
        ]);

    $response->assertStatus(401); // Invalid credentials (user not found in tenant B)
});

test('login requires tenant context', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'any@example.com',
        'password' => 'any',
    ]);

    $response->assertStatus(400)
             ->assertJsonFragment(['message' => 'Tenant identification required. Please provide X-Tenant-ID header or use a tenant domain.']);
});
