<?php

use App\Actions\JobCard\RecalculateJobCardTotalsAction;
use App\Models\JobCard;
use App\Models\JobCardItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it correctly calculates job card totals', function () {
    // Arrange
    $jobCard = JobCard::factory()->create([
        'discount_amount' => 10.00, // $10 discount on whole job
    ]);

    // Item 1: $100 * 2 qty = $200. Tax 10% = $20. Total = $220.
    JobCardItem::factory()->create([
        'job_card_id' => $jobCard->id,
        'unit_price' => 100.00,
        'quantity' => 2,
        'tax_rate' => 10, // 10%
        'discount' => 0,
        'item_type' => 'service',
        'total' => 220, // Ignored by action, but needed for DB constraint if any
    ]);

    // Item 2: $50 * 1 qty = $50. Tax 0%. Discount $5. Total = $45.
    JobCardItem::factory()->create([
        'job_card_id' => $jobCard->id,
        'unit_price' => 50.00,
        'quantity' => 1,
        'tax_rate' => 0,
        'discount' => 5.00,
        'item_type' => 'part',
        'total' => 45,
    ]);

    // Expected Subtotal: 200 + 50 = 250
    // Expected Tax: 20 + 0 = 20
    // Expected Total Items: 220 + 45 = 265
    // Final Total: 265 - 10 (Job Discount) = 255

    // Act
    $action = new RecalculateJobCardTotalsAction();
    $updatedJobCard = $action->execute($jobCard);

    // Assert
    expect($updatedJobCard->subtotal)->toEqual(250.00)
        ->and($updatedJobCard->tax_amount)->toEqual(20.00)
        ->and($updatedJobCard->total_amount)->toEqual(255.00);
});
