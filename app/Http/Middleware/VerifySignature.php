<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SignatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySignature
{
    private SignatureService $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header or query parameter
        $apiKey = $request->header('X-API-Key') ?: $request->query('api_key');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API key is required',
            ], 401);
        }
        
        // Find user by API key
        $user = User::where('api_key', $apiKey)->first();
        
        if (!$user || !$user->secret_key) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ], 401);
        }
        
        // Verify request signature
        $isValid = $this->signatureService->verifyRequestSignature(
            $request,
            $user->secret_key
        );
        
        if (!$isValid) {
            Log::warning('Invalid request signature', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'threat_type' => 'invalid_signature',
                'severity' => 3,
            ]);
            
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid request signature',
            ], 401);
        }
        
        // Attach user to request
        $request->merge(['authenticated_user' => $user]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        return $next($request);
    }
}