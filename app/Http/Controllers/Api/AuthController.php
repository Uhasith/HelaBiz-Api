<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'tenant_name' => 'required|string|max:255',
            'currency' => 'nullable|string|size:3',
        ]);

        // Create tenant
        $tenant = Tenant::create([
            'business_name' => $validated['tenant_name'],
            'email' => $validated['email'],
            'currency' => $validated['currency'] ?? 'LKR',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function verifyWorkOS(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            \WorkOS\WorkOS::setApiKey(config('services.workos.api_key', env('WORKOS_API_KEY')));
            \WorkOS\WorkOS::setClientId(config('services.workos.client_id', env('WORKOS_CLIENT_ID')));

            $profileAndToken = (new \WorkOS\UserManagement())->authenticateWithCode(
                config('services.workos.client_id', env('WORKOS_CLIENT_ID')),
                $request->code
            );

            $workosUser = $profileAndToken->user;
            
            // Check if user exists by email
            $user = User::where('email', $workosUser->email)->first();

            // Auto-register if no account found
            if (!$user) {
                // Determine a safe tenant name
                $businessName = ($workosUser->firstName ?? 'User') . "'s Business";
                
                $tenant = Tenant::create([
                    'business_name' => $businessName,
                    'email' => $workosUser->email,
                    'currency' => 'LKR',
                ]);
                
                $user = User::create([
                    'name' => trim(($workosUser->firstName ?? '') . ' ' . ($workosUser->lastName ?? '')),
                    'email' => $workosUser->email,
                    'password' => Hash::make(str()->random(24)),
                    'tenant_id' => $tenant->id,
                ]);
            }

            // Create standard Sanctum API token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'WorkOS Authentication Failed', 
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
