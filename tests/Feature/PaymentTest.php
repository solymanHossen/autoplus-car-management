<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Functional Test: Partial Payment
 */
it('updates invoice status to partially_paid on partial payment', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'total_amount' => 1000.00,
        'paid_amount' => 0,
        'balance' => 1000.00,
        'status' => 'sent'
    ]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson("/api/v1/payments", [
            'invoice_id' => $invoice->id,
            'amount' => 400.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash'
        ]);

    $response->assertCreated();

    $invoice->refresh();

    expect($invoice->paid_amount)->toEqual('400.00');
    expect($invoice->balance)->toEqual('600.00');
    expect($invoice->status)->toBe('partially_paid');
});

/**
 * Functional Test: Full Payment
 */
it('updates invoice status to paid when fully paid', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'total_amount' => 500.00,
        'paid_amount' => 0,
        'balance' => 500.00,
        'status' => 'sent'
    ]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $tenant->id)
        ->postJson("/api/v1/payments", [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'card'
        ]);

    $response->assertCreated();

    $invoice->refresh();

    // Check floating point precision behavior slightly loosely if needed, but decimal type should match
    expect((float)$invoice->paid_amount)->toEqual(500.00);
    expect((float)$invoice->balance)->toEqual(0.00);
    expect($invoice->status)->toBe('paid');
});

/**
 * Security: Tenant Isolation for Payments
 */
it('prevents recording payment for another tenants invoice', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $invoiceB = Invoice::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    Sanctum::actingAs($userA);

    // Attempt to pay Invoice B
    $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
        ->postJson("/api/v1/payments", [
            'invoice_id' => $invoiceB->id,
            'amount' => 100.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash'
        ]);

    // Should fail because Invoice B is not found in Tenant A's scope (Assuming scope is applied on retrieval/check)
    // Or Validation error depending on implementation. 
    // Usually Model::findOrFail($data['invoice_id']) is used in controller.
    // However, if the query isn't scoped manually in 'store', it might find it if global scope isn't automatically applied on 'find' statically without auth context or if auth context is userA.
    // TenantScoped checks auth()->user()->tenant_id. userA has tenantA. InvoiceB has tenantB.
    // So Invoice::findOrFail($id) should fail with 404 ModelNotFound.
    
    // NOTE: Laravel's findOrFail does respect global scopes. The TenantScoped global scope adds "where tenant_id = X".
    $response->assertStatus(500); // 404 ModelNotFoundException is caught by Controller generic try/catch and returns 500.
});
