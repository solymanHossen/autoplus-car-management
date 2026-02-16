<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            // Check for potential tenant hopping
            if (auth('sanctum')->check()) {
                // Use strict comparison as requested
                if ((string) auth('sanctum')->user()->tenant_id !== (string) $tenant->id) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }

            // Only set the tenant instance if validation passes
            app()->instance('tenant', $tenant);
            auth()->setDefaultDriver('sanctum');
        }

        return $next($request);
    }

    /**
     * Resolve the tenant from the request.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        // 1. Priority: Check for explicit API Header (supports Postman/Mobile Apps)
        if ($tenant = $this->resolveByHeader($request)) {
            return $tenant;
        }

        // 2. Standard: Use configured identification method
        $identificationMethod = config('tenant.identification_method', 'domain');

        return match ($identificationMethod) {
            'domain' => $this->resolveByDomain($request),
            'subdomain' => $this->resolveBySubdomain($request),
            'header' => $this->resolveByHeader($request),
            'path' => $this->resolveByPath($request),
            default => null,
        };
    }

    /**
     * Resolve tenant by full domain.
     */
    protected function resolveByDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        return Tenant::where('domain', $host)->first();
    }

    /**
     * Resolve tenant by subdomain.
     */
    protected function resolveBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        if (! $subdomain || in_array($host, config('tenant.central_domains', []), true)) {
            return null;
        }

        return Tenant::where('subdomain', $subdomain)->first();
    }

    /**
     * Resolve tenant by path segment (first URI segment).
     */
    protected function resolveByPath(Request $request): ?Tenant
    {
        $segment = $request->segment(1);

        if (! $segment || in_array($segment, ['api', 'v1'], true)) {
            return null;
        }

        return Tenant::where('subdomain', $segment)
            ->orWhere('id', $segment)
            ->first();
    }

    /**
     * Resolve tenant by header.
     */
    protected function resolveByHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }
}
