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
use Illuminate\Validation\ValidationException;

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
        $data = $request->validated();

        // Strictly require tenant_id
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The tenant_id field is required.'],
            ]);
        }

        $data['tenant_id'] = $tenantId;
        $data['role'] = $request->input('role', 'mechanic');

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
    }

    /**
     * Login user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
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
