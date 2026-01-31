<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication API Controller
 */
class AuthController extends ApiController
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Get tenant_id from the authenticated user or from request
            // For now, we'll use a default tenant or require it in the request
            $data['tenant_id'] = $request->input('tenant_id') ?? auth()->user()?->tenant_id ?? '1';
            $data['role'] = $request->input('role', 'technician');

            $user = User::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $this->createToken($user);

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Login user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (! Auth::attempt($credentials)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            $user = Auth::user();
            $token = $this->createToken($user);

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            // If using Sanctum tokens
            if (method_exists($user, 'currentAccessToken')) {
                $user->currentAccessToken()->delete();
            }

            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Refresh the authentication token.
     */
    public function refresh(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();

            return $this->successResponse(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user: '.$e->getMessage(), 500);
        }
    }

    /**
     * Create a new token for the user.
     */
    private function createToken(User $user): string
    {
        // Using Laravel Sanctum
        if (method_exists($user, 'createToken')) {
            return $user->createToken('auth_token')->plainTextToken;
        }

        // Fallback to a simple token (not recommended for production)
        return base64_encode($user->id.'|'.time());
    }
}
