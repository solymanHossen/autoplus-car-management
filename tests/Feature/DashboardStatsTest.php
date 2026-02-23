<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns real tenant scoped dashboard stats', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $otherTenant = Tenant::factory()->create();

    $customers = Customer::factory()->count(2)->create(['tenant_id' => $tenant->id]);
    Customer::factory()->count(2)->create(['tenant_id' => $otherTenant->id]);

    $vehicle = Vehicle::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
    ]);

    JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'pending',
    ]);
    JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'working',
    ]);
    JobCard::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'delivered',
    ]);

    Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'job_card_id' => null,
        'balance' => 120.00,
    ]);
    Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'job_card_id' => null,
        'balance' => 0.00,
    ]);

    Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'vehicle_id' => $vehicle->id,
        'appointment_date' => now()->toDateString(),
    ]);
    Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customers->first()->id,
        'vehicle_id' => $vehicle->id,
        'appointment_date' => now()->addDay()->toDateString(),
    ]);

    Sanctum::actingAs($user);

    $response = $this
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson('/api/v1/dashboard/stats');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_customers', 2)
        ->assertJsonPath('data.active_job_cards', 2)
        ->assertJsonPath('data.pending_invoices', 1)
        ->assertJsonPath('data.today_appointments', 1);
});
