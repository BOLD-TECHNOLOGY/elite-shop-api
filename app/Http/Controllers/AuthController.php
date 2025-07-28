<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $fields['password'] = Hash::make($fields['password']);
            $fields['role'] = 'user';
            $fields['user_id'] = User::generateUserId($fields['role']);

            \Log::info('Creating user with fields:', $fields);
            
            $user = User::create($fields);
            $token = $user->createToken($request->name ?? $user->name);

            return response()->json([
                'user' => $user,
                'token' => $token->plainTextToken,
                'dashboard_route' => $user->getDashboardRoute()
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Registration error:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Authenticate user and return token
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], 422);
            }

            $token = $user->createToken($user->name);

            return response()->json([
                'user' => $user,
                'token' => $token->plainTextToken,
                'dashboard_route' => $user->getDashboardRoute()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Login error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Logout user and revoke all tokens
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
