<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaxRate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants (or create a demo tenant if none exists)
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            // Create a demo tenant for seeding
            $tenant = Tenant::create([
                'name' => 'Demo Garage',
                'domain' => 'demo.autopulse.com',
                'subdomain' => 'demo',
                'subscription_status' => 'active',
                'trial_ends_at' => now()->addDays(30),
            ]);
            $tenants = collect([$tenant]);
        }

        // Common tax rates to seed for each tenant
        $taxRates = [
            [
                'name' => 'Standard VAT',
                'rate' => 15.00,
                'is_active' => true,
            ],
            [
                'name' => 'Reduced VAT',
                'rate' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Zero Rate',
                'rate' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'Luxury Tax',
                'rate' => 25.00,
                'is_active' => false,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($taxRates as $taxRate) {
                TaxRate::create([
                    'tenant_id' => $tenant->id,
                    'name' => $taxRate['name'],
                    'rate' => $taxRate['rate'],
                    'is_active' => $taxRate['is_active'],
                ]);
            }
        }
    }
}
