<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Actions\Auth\RegisterTenantAction;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterTenantRequest;
use App\Http\Resources\UserResource;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication API Controller
 */
class AuthController extends ApiController
{
    public function __construct(
        protected RegisterTenantAction $registerTenantAction
    ) {}

    /**
     * Register a new Tenant (SaaS Signup).
     */
    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->registerTenantAction->execute($request->validated());
        $user = $result['user'];

        $token = $this->createToken($user);

        return $this->successResponse([
            'user' => new UserResource($user),
            'tenant' => $result['tenant'],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Tenant registered successfully', 201);
    }

    /**
     * Login user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Security: Ensure we are in a tenant context before attempting login.
        // Because emails are not unique globally, we MUST know which tenant to look in.
        if (! app()->bound('tenant')) {
            return $this->errorResponse('Tenant identification required. Please provide X-Tenant-ID header or use a tenant domain.', 400);
        }

        $credentials = $request->validated();
        $ipAddress = (string) $request->ip();
        $userAgent = substr((string) $request->userAgent(), 0, 1024);

        // User lookup is scoped by the TenantScoped trait implicitly.
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            LoginAttempt::create([
                'email' => $credentials['email'],
                'ip_address' => $ipAddress,
                'successful' => false,
                'user_agent' => $userAgent,
            ]);

            return $this->errorResponse('Invalid credentials', 401);
        }

        // Security: Verify user belongs to current tenant (Defense in Depth)
        // This is technically redundant due to TenantScoped but vital for security logic auditing.
        if ($user->tenant_id !== app('tenant')->id) {
             LoginAttempt::create([
                'email' => $credentials['email'],
                'ip_address' => $ipAddress,
                'successful' => false,
                'user_agent' => $userAgent,
            ]);

             return $this->errorResponse('Access denied.', 403);
        }

        // Manually log in the user if needed by other logic, but usually for API we just issue token
        // Auth::login($user); 

        $token = $this->createToken($user);

        LoginAttempt::create([
            'email' => $credentials['email'],
            'ip_address' => $ipAddress,
            'successful' => true,
            'user_agent' => $userAgent,
        ]);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // If using Sanctum tokens
        if (method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Refresh the authentication token.
     */
    public function refresh(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Revoke old token
        if (method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()->delete();
        }

        // Create new token
        $token = $this->createToken($user);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully');
    }

    /**
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        return $this->successResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Create a new token for the user.
     * 
     * @throws \RuntimeException If token creation fails.
     */
    private function createToken(User $user): string
    {
        // Security: Ensure the user model uses Sanctum's HasApiTokens
        if (! method_exists($user, 'createToken')) {
            // Log this critical configuration error in a real app
            throw new \RuntimeException('Server Error: User model configuration invalid for API authentication.', 500);
        }

        $tokenDescriptor = $user->createToken('auth_token');

        if (! $tokenDescriptor) {
            throw new \RuntimeException('Server Error: Failed to generate access token.', 500);
        }

        return $tokenDescriptor->plainTextToken;
    }
}
