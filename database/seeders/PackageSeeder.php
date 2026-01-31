<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small workshops just getting started',
                'price_monthly' => 29.99,
                'price_yearly' => 299.99,
                'max_users' => 3,
                'max_vehicles' => 100,
                'max_storage_gb' => 5,
                'features' => [
                    'Customer & Vehicle Management',
                    'Job Card Management',
                    'Basic Invoicing',
                    'Email Notifications',
                    'Mobile App Access',
                ],
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing workshops with multiple technicians',
                'price_monthly' => 79.99,
                'price_yearly' => 799.99,
                'max_users' => 10,
                'max_vehicles' => 500,
                'max_storage_gb' => 25,
                'features' => [
                    'Everything in Starter',
                    'Inventory Management',
                    'Advanced Reporting',
                    'SMS & WhatsApp Notifications',
                    'Service Templates',
                    'Appointment Scheduling',
                    'Customer Feedback System',
                ],
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large workshops and multi-location operations',
                'price_monthly' => 199.99,
                'price_yearly' => 1999.99,
                'max_users' => 50,
                'max_vehicles' => -1, // Unlimited
                'max_storage_gb' => 100,
                'features' => [
                    'Everything in Professional',
                    'AI-Powered Diagnostics',
                    'Multi-Location Support',
                    'Custom Integrations (Webhooks)',
                    'Priority Support',
                    'White-Label Options',
                    'Advanced Security Features',
                    'Dedicated Account Manager',
                ],
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
