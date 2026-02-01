<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobCard;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    //
});

/**
 * Functional Test: Invoice Generation from Job Card
 */
it('can create an invoice manually with correct calculations', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $jobCard = JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'total_amount' => 150.00
    ]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson("/api/v1/invoices", [
            'customer_id' => $customer->id,
            'job_card_id' => $jobCard->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 10.00, 
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'sent',
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('invoices', [
        'tenant_id' => $tenant->id,
        'job_card_id' => $jobCard->id,
        'total_amount' => 100.00, 
        'balance' => 100.00, 
        'status' => 'sent',
    ]);
});

/**
 * Security: Cross-Tenant Access
 */
it('prevents accessing invoices from another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $invoiceB = Invoice::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    Sanctum::actingAs($userA);

    $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->getJson("/api/v1/invoices/{$invoiceB->id}");

    $response->assertNotFound();
});

/**
 * Validation: Required Fields
 */
it('requires customer_id to create an invoice', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson("/api/v1/invoices", [
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100.00
        ]);

    $response->assertJsonValidationErrors(['customer_id']);
});
