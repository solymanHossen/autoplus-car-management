<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('supports supplier crud in tenant scope', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $create = $this->postJson('/api/v1/suppliers', [
            'name' => 'Best Parts Ltd',
            'phone' => '1234567890',
            'email' => 'parts@example.com',
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

    $create->assertCreated()->assertJsonPath('data.name', 'Best Parts Ltd');
    $supplierId = $create->json('data.id');

    $this->getJson('/api/v1/suppliers', [
            'X-Tenant-ID' => $tenant->id,
        ])
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    $this->putJson('/api/v1/suppliers/'.$supplierId, [
            'name' => 'Best Parts Updated',
            'phone' => '1234567890',
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Best Parts Updated');

    $this->deleteJson('/api/v1/suppliers/'.$supplierId, [], [
            'X-Tenant-ID' => $tenant->id,
        ])
        ->assertOk();
});

it('supports product and tax-rate creation in tenant scope', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user);

    $supplier = Supplier::create([
        'tenant_id' => $tenant->id,
        'name' => 'Supplier X',
        'phone' => '5550001',
    ]);

    $this->postJson('/api/v1/products', [
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-1001',
            'name' => 'Brake Pad',
            'type' => 'part',
            'unit_price' => 150,
            'cost_price' => 100,
            'stock_quantity' => 10,
            'min_stock_level' => 2,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.sku', 'SKU-1001');

    $this->postJson('/api/v1/tax-rates', [
            'name' => 'VAT',
            'rate' => 15,
            'is_active' => true,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'VAT');

    expect(Product::query()->count())->toBe(1)
        ->and(TaxRate::query()->count())->toBe(1);
});

it('prevents cross-tenant visibility for inventory core modules', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();

    Supplier::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Supplier',
        'phone' => '111',
    ]);

    Supplier::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Supplier',
        'phone' => '222',
    ]);

    Sanctum::actingAs($userA);

    $this->getJson('/api/v1/suppliers', [
            'X-Tenant-ID' => $tenantA->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Tenant A Supplier'])
        ->assertJsonMissing(['name' => 'Tenant B Supplier']);
});
