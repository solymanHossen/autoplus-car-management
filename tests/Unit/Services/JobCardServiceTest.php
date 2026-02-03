<?php

use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\Tenant;
use App\Services\JobCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recalculateTotals correctly sums items and updates job card', function () {
    $tenant = Tenant::factory()->create();
    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'subtotal' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
        'discount_amount' => 10.00,
    ]);

    // Helper to create items (Factory missing)
    $jobCard->jobCardItems()->create([
        'tenant_id' => $tenant->id,
        'item_type' => 'part',
        'quantity' => 2,
        'unit_price' => 100.00,
        'tax_rate' => 10.00,
        'total' => 220.00, // (100 * 2) + 10% tax
    ]);

    $jobCard->jobCardItems()->create([
        'tenant_id' => $tenant->id,
        'item_type' => 'service',
        'quantity' => 1,
        'unit_price' => 50.00,
        'tax_rate' => 0,
        'total' => 50.00,
    ]);

    $service = app(JobCardService::class);
    $updatedJobCard = $service->recalculateTotals($jobCard);

    // Assertions
    // Subtotal: 200 + 50 = 250
    // Tax: 20 + 0 = 20
    // Total from Items: 220 + 50 = 270
    // Final Total: 270 - 10 (discount) = 260
    
    expect($updatedJobCard->subtotal)->toEqual(250.00)
        ->and($updatedJobCard->tax_amount)->toEqual(20.00)
        ->and($updatedJobCard->total_amount)->toEqual(260.00);
});

test('recalculateTotals handles empty items', function () {
    $tenant = Tenant::factory()->create();
    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'subtotal' => 100, // garbage data
        'tax_amount' => 10,
        'total_amount' => 110,
        'discount_amount' => 0,
    ]);

    $service = app(JobCardService::class);
    $updatedJobCard = $service->recalculateTotals($jobCard);

    expect($updatedJobCard->subtotal)->toEqual(0)
        ->and($updatedJobCard->tax_amount)->toEqual(0)
        ->and($updatedJobCard->total_amount)->toEqual(0);
});
