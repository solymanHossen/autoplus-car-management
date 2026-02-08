<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait UserTenantScoped
{
    /**
     * Boot the tenant scoped trait for the User model.
     * Unlike generic TenantScoped, this allows querying users when no tenant/auth is present
     * to facilitate the Login process.
     */
    protected static function bootUserTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = null;

            if (auth()->hasUser()) {
                $tenantId = auth()->user()->tenant_id;
            } elseif (app()->bound('tenant')) {
                $tenantId = app('tenant')->id;
            }

            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            } 
            // If no tenant context, DO NOT hide users. 
            // This is required for:
            // 1. Login (needs to find user by email)
            // 2. Password Reset
            // 3. Registration checks
        });

        static::creating(function (Model $model) {
            if (!empty($model->tenant_id)) {
                return;
            }

            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            } elseif (app()->bound('tenant')) {
                $model->tenant_id = app('tenant')->id;
            } 
            // Allow creating users without tenant (e.g. system admins in future) 
            // or let the database complaint if it's required.
            // For now, let's keep it safe but maybe not throw Exception if we are just registering explicitly?
            // The original trait threw RuntimeException. 
            // If we are registering via our custom Register page, we set tenant_id manually so this block is skipped.
            
            // If we try to create a user via Artisan or elsewhere without tenant, it might fail if column is not nullable.
            // Let's keep the throw to be safe/explicit.
            else {
                 // Check if it's running in console or specific exclusions? 
                 // For now, keep inconsistent state protection.
                 // Actually, if we are in the Register flow, we set tenant_id BEFORE save, so this logic is skipped.
                 throw new RuntimeException('Tenant ID is missing. Cannot save orphan user.');
            }
        });
    }
}
