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
        // User::factory(10)->create();

        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.autopulse.local',
            'subdomain' => 'test',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);
    }
}
