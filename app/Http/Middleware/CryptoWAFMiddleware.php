<?php

namespace App\Http\Middleware;

use App\WAF\Detectors\SQLInjectionDetector;
use App\WAF\Detectors\XSSDetector;
use App\WAF\Detectors\BruteForceDetector;
use App\WAF\Logger\WAFLogger;
use App\Models\BlockedIP;
use App\Models\User;
use App\Services\SignatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CryptoWAFMiddleware
{
    private SQLInjectionDetector $sqlDetector;
    private XSSDetector $xssDetector;
    private BruteForceDetector $bruteForceDetector;
    private WAFLogger $logger;
    private SignatureService $signatureService;

    public function __construct(
        SQLInjectionDetector $sqlDetector,
        XSSDetector $xssDetector,
        BruteForceDetector $bruteForceDetector,
        WAFLogger $logger,
        SignatureService $signatureService
    ) {
        $this->sqlDetector = $sqlDetector;
        $this->xssDetector = $xssDetector;
        $this->bruteForceDetector = $bruteForceDetector;
        $this->logger = $logger;
        $this->signatureService = $signatureService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Log request for debugging
        Log::debug('WAF Processing:', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);
        
        // 2. Check if WAF is enabled
        if (!config('waf.enabled', true)) {
            return $next($request);
        }
        
        // 3. Skip WAF for safe GET requests (forms, dashboards, views)
        // AND skip WAF for POST requests to CRYPTO operations (user submitting their own data)
        if ($request->isMethod('GET')) {
            $safePaths = [
                'crypto',
                'dashboard',
                'crypto/encrypt-form',
                'crypto/decrypt-form',
                'crypto/hmac-form',
                'crypto/sign-form',
                'crypto/hash-form',
                'login',
                'register',
                'api-docs',
                'profile',
                'api-keys',
            ];
            
            foreach ($safePaths as $path) {
                if ($request->path() === $path || $request->is($path . '*')) {
                    return $next($request);
                }
            }
        }
        
        // Skip WAF for crypto POST operations (user-submitted crypto data)
        if ($request->isMethod('POST') && $request->is('crypto/*')) {
            Log::debug('WAF: Skipping scan for crypto operation', [
                'path' => $request->path(),
                'operation' => basename($request->path()),
            ]);
            return $next($request);
        }
        
        // 4. IP Blocking Check
        if ($this->isIPBlocked($request)) {
            $this->logger->logThreat($request, 'blocked_ip', 'Blocked IP attempted access', 5, true);
            return response()->json(['error' => 'Access denied', 'message' => 'Your IP address has been blacklisted.'], 403);
        }

        // 5. AUTOMATIC PROTECTION: CRYPTOGRAPHIC SIGNATURE & REPLAY PROTECTION
        if ($request->is('api/*')) {
            $apiKey = $request->header('X-API-Key');
            $signature = $request->header('X-Signature');
            $nonce = $request->header('X-Nonce');

            // Find User & Validate Credentials
            $user = User::where('api_key', $apiKey)->first();

            // A. Integrity Check (Milestone: Integrity Verified)
            if (!$user || !$signature || !$this->signatureService->verify($request, $user->secret_key)) {
                // Milestone: Observability
                Log::warning('WAF: Signature Validation Failed', [
                    'ip' => $request->ip(),
                    'api_key' => $apiKey ?? 'MISSING',
                    'url' => $request->fullUrl()
                ]);
                
                $this->logger->logThreat($request, 'invalid_signature', 'Signature mismatch, missing, or unauthorized API Key', 5, true);
                return response()->json(['status' => 'error', 'message' => 'Security integrity check failed (Invalid Signature)'], 401);
            }

            // B. Replay Protection (Milestone: Replay Protection)
            if (!$nonce) {
                return response()->json(['status' => 'error', 'message' => 'Security header X-Nonce is required'], 401);
            }

            if (Cache::has('waf_nonce_' . $nonce)) {
                Log::alert('WAF: Replay Attack Detected!', ['nonce' => $nonce, 'ip' => $request->ip()]);
                $this->logger->logThreat($request, 'replay_attack', 'Duplicate request signature (Replay Attack)', 5, true);
                return response()->json(['status' => 'error', 'message' => 'Request has already been processed (Replay Protection)'], 429);
            }

            // Store nonce (Redis/Database recommended for production)
            Cache::put('waf_nonce_' . $nonce, true, now()->addMinutes(10));
        }
        
        // 6. Content Scanning (SQLi, XSS, Brute Force)
        $threats = [];
        $threats = array_merge($threats, $this->sqlDetector->detect($request));
        $threats = array_merge($threats, $this->xssDetector->detect($request));
        
        if ($this->isAuthRoute($request)) {
            $threats = array_merge($threats, $this->bruteForceDetector->detect($request));
        }
        
        // 7. Process threats
        if (!empty($threats)) {
            return $this->handleThreats($request, $threats, $next);
        }
        
        // Log normal request (Optional: only for high-security audits)
        // $this->logger->logRequest($request);
        
        return $next($request);
    }

    private function isIPBlocked(Request $request): bool
    {
        return BlockedIP::where('ip_address', $request->ip())
            ->where(function ($query) {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
            })->exists();
    }

    private function isAuthRoute(Request $request): bool
    {
        $path = $request->path();
        $authRoutes = ['login', 'register', 'password/reset', 'auth'];
        foreach ($authRoutes as $route) {
            if (str_contains($path, $route)) return true;
        }
        return false;
    }

    private function handleThreats(Request $request, array $threats, Closure $next): Response
    {
        $blockRequest = false;
        $highestSeverity = 0;
        
        foreach ($threats as $threat) {
            $this->logger->logThreat(
                $request,
                $threat['type'],
                $threat['description'] ?? 'Threat detected',
                $threat['severity'],
                $threat['blocked'] ?? false,
                $threat
            );
            
            if ($threat['severity'] > $highestSeverity) $highestSeverity = $threat['severity'];
            if ($threat['blocked'] ?? false) $blockRequest = true;
        }
        
        // Hard Block for Critical Threats
        if ($blockRequest || $highestSeverity >= 4) {
            Log::alert('WAF: Request blocked due to high-severity threat', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'highest_severity' => $highestSeverity,
                'threats_count' => count($threats),
                'threat_types' => array_map(fn($t) => $t['type'], $threats),
                'threat_details' => $threats,
            ]);
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Security policy violation',
                    'details' => 'Request blocked by WAF',
                    'threats' => array_map(fn($t) => ['type' => $t['type'], 'description' => $t['description'] ?? null], $threats)
                ], 403);
            }
            
            // Include threat details in web response for debugging
            $threatSummary = implode(', ', array_map(fn($t) => $t['type'] . ' (' . ($t['description'] ?? 'no details') . ')', $threats));
            return redirect()->route('crypto.dashboard')
                ->with('error', 'Your request was blocked by the security system: ' . $threatSummary);
        }
        
        // Soft Protection: Sanitize and Continue
        $this->sanitizeRequest($request, $threats);
        return $next($request);
    }

    private function sanitizeRequest(Request $request, array $threats): void
    {
        foreach ($threats as $threat) {
            $param = $threat['parameter'] ?? null;
            if (!$param) continue;

            $value = $request->input($param);
            if ($value && is_string($value)) {
                $sanitized = ($threat['type'] === 'sql_injection') 
                    ? $this->sqlDetector->sanitize($value) 
                    : $this->xssDetector->sanitize($value);
                $request->merge([$param => $sanitized]);
            }
        }
    }
}