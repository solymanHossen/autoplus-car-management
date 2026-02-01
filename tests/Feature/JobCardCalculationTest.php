<?php

use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('job card totals are calculated correctly when adding items', function () {
    // Setup Tenant and User
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Create Job Card
    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'subtotal' => 0,
        'tax_amount' => 0,
        'discount_amount' => 0, // Ensure no random discount from factory
        'total_amount' => 0,
    ]);

    $this->actingAs($user);

    // 1. Add First Item: Part $100, Qty 2, Tax 10%
    // Subtotal: 200
    // Tax: 20
    // Total: 220
    $response = $this->postJson(route('job-cards.add-item', $jobCard), [
        'item_type' => 'part',
        'quantity' => 2,
        'unit_price' => 100,
        'tax_rate' => 10,
        'discount' => 0,
        'notes' => 'Test Part',
    ]);

    $response->assertCreated();
    
    // Verify Item 1 Response
    $response->assertJsonFragment([
        'total' => '220.00',
    ]);

    // Verify Job Card Totals after Item 1
    $jobCard->refresh();
    expect($jobCard->subtotal)->toEqual('200.00')
        ->and($jobCard->tax_amount)->toEqual('20.00')
        ->and($jobCard->total_amount)->toEqual('220.00');


    // 2. Add Second Item: Service $50, Qty 1, Tax 0%
    // Subtotal: 50
    // Tax: 0
    // Total: 50
    $response2 = $this->postJson(route('job-cards.add-item', $jobCard), [
        'item_type' => 'service',
        'quantity' => 1,
        'unit_price' => 50,
        'tax_rate' => 0,
        'discount' => 0,
        'notes' => 'Labor',
    ]);

    $response2->assertCreated();

    // Verify Job Card Totals after Item 2
    // Cumulative Subtotal: 200 + 50 = 250
    // Cumulative Tax: 20 + 0 = 20
    // Cumulative Total: 220 + 50 = 270
    $jobCard->refresh();
    expect($jobCard->subtotal)->toEqual('250.00')
        ->and($jobCard->tax_amount)->toEqual('20.00')
        ->and($jobCard->total_amount)->toEqual('270.00');
});

test('job card discount is applied to global total', function () {
     // Setup Tenant and User
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Create Job Card with global discount of 10
    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'discount_amount' => 10.00, 
        'subtotal' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
    ]);

    $this->actingAs($user);

    // Add Item: 100 * 1, Tax 0
    // Item Total: 100
    // Job Total should be: 100 - 10 = 90
    $this->postJson(route('job-cards.add-item', $jobCard), [
        'item_type' => 'part',
        'quantity' => 1,
        'unit_price' => 100,
        'tax_rate' => 0,
    ]);

    $jobCard->refresh();

    expect($jobCard->subtotal)->toEqual('100.00')
        ->and($jobCard->total_amount)->toEqual('90.00'); // 100 - 10 discount
});
