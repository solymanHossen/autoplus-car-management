<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo tenant
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'demo.autopulse.com'],
            [
                'name' => 'Demo Auto Workshop',
                'subdomain' => 'demo',
                'logo_url' => null,
                'primary_color' => '#3B82F6',
                'subscription_status' => 'active',
                'trial_ends_at' => now()->addDays(30),
            ]
        );

        // Create demo users for different roles
        $users = [
            [
                'name' => 'John Owner',
                'email' => 'owner@demo.autopulse.com',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'phone' => '+1234567890',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Sarah Manager',
                'email' => 'manager@demo.autopulse.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '+1234567891',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Mike Advisor',
                'email' => 'advisor@demo.autopulse.com',
                'password' => Hash::make('password'),
                'role' => 'advisor',
                'phone' => '+1234567892',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Tom Mechanic',
                'email' => 'mechanic@demo.autopulse.com',
                'password' => Hash::make('password'),
                'role' => 'mechanic',
                'phone' => '+1234567893',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Lisa Accountant',
                'email' => 'accountant@demo.autopulse.com',
                'password' => Hash::make('password'),
                'role' => 'accountant',
                'phone' => '+1234567894',
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Create demo customers
        $customers = [
            [
                'tenant_id' => $tenant->id,
                'name' => 'Robert Smith',
                'email' => 'robert.smith@example.com',
                'phone' => '+1234567895',
                'address' => '123 Main Street',
                'city' => 'New York',
                'postal_code' => '10001',
                'preferred_language' => 'en',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Emily Johnson',
                'email' => 'emily.johnson@example.com',
                'phone' => '+1234567896',
                'address' => '456 Oak Avenue',
                'city' => 'Los Angeles',
                'postal_code' => '90001',
                'preferred_language' => 'en',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Ahmed Rahman',
                'email' => 'ahmed.rahman@example.com',
                'phone' => '+1234567897',
                'address' => '789 Pine Road',
                'city' => 'Chicago',
                'postal_code' => '60601',
                'preferred_language' => 'en',
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);

            // Create vehicles for each customer
            Vehicle::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'registration_number' => 'ABC-'.rand(1000, 9999),
                'make' => $this->getRandomMake(),
                'model' => 'Model X',
                'year' => rand(2015, 2023),
                'color' => $this->getRandomColor(),
                'current_mileage' => rand(10000, 100000),
            ]);
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Owner: owner@demo.autopulse.com / password');
        $this->command->info('Manager: manager@demo.autopulse.com / password');
        $this->command->info('Advisor: advisor@demo.autopulse.com / password');
        $this->command->info('Mechanic: mechanic@demo.autopulse.com / password');
        $this->command->info('Accountant: accountant@demo.autopulse.com / password');
    }

    private function getRandomMake(): string
    {
        $makes = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Nissan'];

        return $makes[array_rand($makes)];
    }

    private function getRandomColor(): string
    {
        $colors = ['Black', 'White', 'Silver', 'Red', 'Blue', 'Gray', 'Green'];

        return $colors[array_rand($colors)];
    }
}
