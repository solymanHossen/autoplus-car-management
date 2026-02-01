<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
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
            app()->instance('tenant', $tenant);
            auth()->setDefaultDriver('sanctum');

            // Strictly cross-check authenticated user's tenant_id
            // We manually check the token here because this middleware might run before Auth middleware
            $token = $request->bearerToken();
            if ($token) { // Only check if token is provided
                $accessToken = PersonalAccessToken::findToken($token);
                
                // If token exists and resolves to a user
                // Use tokenable_type/id to avoid recursion in TenantScoped
                if ($accessToken) {
                    $type = $accessToken->tokenable_type;
                    $id = $accessToken->tokenable_id;
                    
                    // Assume it's a User or similar with tenant_id, load without scope to allow cross-check
                    if (class_exists($type)) {
                        $user = $type::withoutGlobalScope('tenant')->find($id);
                         
                        if ($user && isset($user->tenant_id) && $user->tenant_id !== $tenant->id) {
                            abort(403, 'Unauthorized access: User belongs to a different tenant.');
                        }
                    }
                }
            }
        }

        return $next($request);
    }

    /**
     * Resolve the tenant from the request.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        $identificationMethod = config('tenant.identification_method', 'domain');

        return match ($identificationMethod) {
            'domain' => $this->resolveByDomain($request),
            'subdomain' => $this->resolveBySubdomain($request),
            'header' => $this->resolveByHeader($request),
            default => null,
        };
    }

    /**
     * Resolve tenant by full domain.
     */
    protected function resolveByDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        return Tenant::where('domain', $host)
            ->orWhere('subdomain', $host)
            ->first();
    }

    /**
     * Resolve tenant by subdomain.
     */
    protected function resolveBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        if (! $subdomain || in_array($host, config('tenant.central_domains', []))) {
            return null;
        }

        return Tenant::where('subdomain', $subdomain)->first();
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
