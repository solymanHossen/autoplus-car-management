<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterTenantAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'domain' => $data['domain'] ?? null,
                'subdomain' => $data['subdomain'] ?? null,
            ]);

            // 2. Create Admin User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => 'owner', // Default to owner for new tenant
                'password' => Hash::make($data['password']),
            ]);

            return ['tenant' => $tenant, 'user' => $user];
        });
    }
}
