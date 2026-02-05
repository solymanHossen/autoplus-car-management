<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tenant A
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'domain' => 'tenant-a.local',
            'subdomain' => 'tenant-a',
        ]);

        User::factory()->create([
            'name' => 'User A',
            'email' => 'test@example.com', // SAME EMAIL
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'password' => bcrypt('password-a'), // Different password
        ]);

        // Tenant B
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'domain' => 'tenant-b.local',
            'subdomain' => 'tenant-b',
        ]);

        User::factory()->create([
            'name' => 'User B',
            'email' => 'test@example.com', // SAME EMAIL
            'tenant_id' => $tenantB->id,
            'role' => 'owner',
            'password' => bcrypt('password-b'), // Different password
        ]);
    }
}
