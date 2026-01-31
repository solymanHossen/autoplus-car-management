<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = auth()->user();
        $rolePermissions = config('permissions.role_permissions', []);
        $userPermissions = $rolePermissions[$user->role] ?? [];

        // Owner has all permissions
        if (in_array('*', $userPermissions)) {
            return $next($request);
        }

        // Check if user has the required permission
        if (!in_array($permission, $userPermissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
