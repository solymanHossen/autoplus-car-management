<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = null;

            // Use hasUser() to avoid infinite recursion when querying User model during auth
            if (auth()->hasUser()) {
                $tenantId = auth()->user()->tenant_id;
            } elseif (app()->bound('tenant')) {
                $tenantId = app('tenant')->id;
            }

            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            } else {
                // Prevent data leaking when auth()->user() is not available
                // and no tenant context is resolved.
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            } elseif (app()->bound('tenant')) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }
}
