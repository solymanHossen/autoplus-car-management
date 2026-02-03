<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Actions\Auth\RegisterTenantAction;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterTenantRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        // Manually log in the user if needed by other logic, but usually for API we just issue token
        // Auth::login($user); 

        $token = $this->createToken($user);

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
