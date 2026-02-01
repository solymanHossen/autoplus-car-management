<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a user can register', function () {
    // Create a tenant so registration can pick it up
    $tenant = Tenant::factory()->create();

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'tenant_id' => $tenant->id,
    ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'user',
            'token',
        ],
        'message',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'tenant_id' => $tenant->id,
    ]);
});

test('a user can login', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'jane@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'jane@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'user',
            'token',
        ],
    ]);
});

test('authenticated user can view their profile', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)
        ->getJson(route('api.v1.auth.me'));

    $response->assertOk();
    $response->assertJsonFragment(['email' => $user->email]);
});

test('unauthenticated user cannot view profile', function () {
    $response = $this->getJson(route('api.v1.auth.me'));

    $response->assertStatus(401); 
});

test('user can logout', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(route('api.v1.auth.logout'));

    $response->assertOk();
    $response->assertJsonFragment(['message' => 'Logged out successfully']);
});
