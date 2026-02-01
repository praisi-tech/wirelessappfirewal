<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'key' => $key,
                'attempts' => $this->limiter->attempts($key),
            ]);
            
            return $this->buildRateLimitResponse($key);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address and user ID if available
        $userId = $request->user()?->id ?: 'guest';
        return sha1($request->ip() . '|' . $userId . '|' . $request->path());
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key);
    }

    protected function buildRateLimitResponse(string $key): Response
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => '60',
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ]);
    }

    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
        
        return $response;
    }
}