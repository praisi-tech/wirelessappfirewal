<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CryptoService;
use App\Services\TokenService;
use App\WAF\Detectors\BruteForceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private CryptoService $crypto;
    private TokenService $tokenService;
    private BruteForceDetector $bruteForceDetector;

    public function __construct(
        CryptoService $crypto,
        TokenService $tokenService,
        BruteForceDetector $bruteForceDetector
    ) {
        $this->crypto = $crypto;
        $this->tokenService = $tokenService;
        $this->bruteForceDetector = $bruteForceDetector;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $this->crypto->hashPassword($request->password),
        ]);

        // Generate API keys
        $user->generateApiKey();

        Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'api_key' => $user->api_key,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Check brute force protection
        if ($this->bruteForceDetector->isBlocked($request->ip())) {
            throw ValidationException::withMessages([
                'email' => ['Too many failed attempts. IP address is blocked.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->bruteForceDetector->recordFailedAttempt($request, $request->email);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is locked
        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Account is temporarily locked. Please try again later.'],
            ]);
        }

        // Verify password
        if (!$this->crypto->verifyPassword($request->password, $user->password)) {
            $user->incrementLoginAttempts();
            $this->bruteForceDetector->recordFailedAttempt($request, $request->email);
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Reset login attempts on successful login
        $user->resetLoginAttempts();
        $user->last_login_at = now();
        $user->save();

        $this->bruteForceDetector->recordSuccessfulAttempt($request, $request->email);

        // Generate token
        $token = $this->tokenService->createToken($user, [
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
        ]);

        Log::info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $this->tokenService->revokeToken($token);
        }

        Log::info('User logged out', ['user_id' => Auth::id()]);

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function generateApiKey(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $apiData = $this->tokenService->generateApiToken($user);

        Log::info('API key generated', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'API key generated successfully',
            'api_key' => $apiData['api_key'],
            'secret_key' => $user->secret_key,
            'signature_data' => [
                'timestamp' => $apiData['timestamp'],
                'nonce' => $apiData['nonce'],
                'signature' => $apiData['signature'],
            ],
            'warning' => 'Store your secret key securely. It will not be shown again.',
        ]);
    }

    public function revokeApiKey(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->api_key = null;
        $user->secret_key = null;
        $user->save();

        // Revoke all tokens
        $this->tokenService->revokeAllUserTokens($user);

        Log::info('API key revoked', ['user_id' => $user->id]);

        return response()->json(['message' => 'API key revoked successfully']);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'last_login_at' => $user->last_login_at,
                'has_api_key' => !empty($user->api_key),
                'created_at' => $user->created_at,
            ],
        ]);
    }
}