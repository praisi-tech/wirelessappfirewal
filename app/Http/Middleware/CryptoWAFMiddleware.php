<?php

namespace App\Http\Middleware;

use App\WAF\Detectors\SQLInjectionDetector;
use App\WAF\Detectors\XSSDetector;
use App\WAF\Detectors\BruteForceDetector;
use App\WAF\Logger\WAFLogger;
use App\Models\BlockedIP;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CryptoWAFMiddleware
{
    private SQLInjectionDetector $sqlDetector;
    private XSSDetector $xssDetector;
    private BruteForceDetector $bruteForceDetector;
    private WAFLogger $logger;

    public function __construct(
        SQLInjectionDetector $sqlDetector,
        XSSDetector $xssDetector,
        BruteForceDetector $bruteForceDetector,
        WAFLogger $logger
    ) {
        $this->sqlDetector = $sqlDetector;
        $this->xssDetector = $xssDetector;
        $this->bruteForceDetector = $bruteForceDetector;
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Check if WAF is enabled
        if (!config('waf.enabled', true)) {
            return $next($request);
        }
        
        // Check if IP is blocked
        if ($this->isIPBlocked($request)) {
            $this->logger->logThreat(
                $request,
                'blocked_ip',
                'Blocked IP attempted access',
                4,
                true
            );
            
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP address has been blocked',
            ], 403);
        }
        
        // Run all detectors
        $threats = [];
        
        // SQL Injection detection
        $sqlThreats = $this->sqlDetector->detect($request);
        $threats = array_merge($threats, $sqlThreats);
        
        // XSS detection
        $xssThreats = $this->xssDetector->detect($request);
        $threats = array_merge($threats, $xssThreats);
        
        // Brute force detection (only for auth routes)
        if ($this->isAuthRoute($request)) {
            $bruteForceThreats = $this->bruteForceDetector->detect($request);
            $threats = array_merge($threats, $bruteForceThreats);
        }
        
        // Process threats
        if (!empty($threats)) {
            return $this->handleThreats($request, $threats);
        }
        
        // Log normal request
        $this->logger->logRequest($request);
        
        return $next($request);
    }

    private function isIPBlocked(Request $request): bool
    {
        $ipAddress = $request->ip();
        
        return BlockedIP::where('ip_address', $ipAddress)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    private function isAuthRoute(Request $request): bool
    {
        $path = $request->path();
        $authRoutes = ['login', 'register', 'password/reset', 'auth'];
        
        foreach ($authRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }
        
        return false;
    }

    private function handleThreats(Request $request, array $threats): Response
    {
        $blockRequest = false;
        $highestSeverity = 0;
        
        foreach ($threats as $threat) {
            // Log each threat
            $this->logger->logThreat(
                $request,
                $threat['type'],
                $threat['description'] ?? 'Threat detected',
                $threat['severity'],
                $threat['blocked'] ?? false,
                $threat
            );
            
            // Update highest severity
            if ($threat['severity'] > $highestSeverity) {
                $highestSeverity = $threat['severity'];
            }
            
            // Block request if any threat requires blocking
            if ($threat['blocked'] ?? false) {
                $blockRequest = true;
            }
        }
        
        // Block request if severity is critical
        if ($highestSeverity >= 4) {
            $blockRequest = true;
        }
        
        if ($blockRequest) {
            Log::warning('Request blocked by WAF', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'threats' => $threats,
            ]);
            
            return response()->json([
                'error' => 'Request blocked',
                'message' => 'Security threat detected',
                'threats' => array_map(function ($threat) {
                    return [
                        'type' => $threat['type'],
                        'severity' => $threat['severity'],
                    ];
                }, $threats),
            ], 403);
        }
        
        // For non-blocking threats, continue but sanitize input
        $this->sanitizeRequest($request, $threats);
        
        return response()->json([
            'warning' => 'Security warnings detected',
            'message' => 'Input has been sanitized',
            'warnings' => array_map(function ($threat) {
                return [
                    'type' => $threat['type'],
                    'severity' => $threat['severity'],
                ];
            }, $threats),
        ], 200);
    }

    private function sanitizeRequest(Request $request, array $threats): void
    {
        foreach ($threats as $threat) {
            if ($threat['type'] === 'sql_injection' && isset($threat['parameter'])) {
                $value = $request->input($threat['parameter']);
                if ($value) {
                    $sanitized = $this->sqlDetector->sanitize($value);
                    $request->merge([$threat['parameter'] => $sanitized]);
                }
            } elseif ($threat['type'] === 'xss' && isset($threat['parameter'])) {
                $value = $request->input($threat['parameter']);
                if ($value) {
                    $sanitized = $this->xssDetector->sanitize($value);
                    $request->merge([$threat['parameter'] => $sanitized]);
                }
            }
        }
    }
}