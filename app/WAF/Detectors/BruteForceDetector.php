<?php

namespace App\WAF\Detectors;

use App\Models\BlockedIP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BruteForceDetector
{
    private int $maxAttempts;
    private int $blockDuration;

    public function __construct()
    {
        $this->maxAttempts = config('waf.brute_force.max_attempts', 5);
        $this->blockDuration = config('waf.brute_force.block_duration', 900);
    }

    public function detect(Request $request, string $identifier = null): array
    {
        $ipAddress = $request->ip();
        $threats = [];
        
        // Check if IP is already blocked
        $blocked = BlockedIP::where('ip_address', $ipAddress)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
        
        if ($blocked) {
            $threats[] = [
                'type' => 'brute_force',
                'description' => 'IP address is blocked',
                'severity' => 4,
                'blocked' => true,
            ];
            return $threats;
        }
        
        // Track login attempts for this IP
        $attemptKey = "login_attempts:{$ipAddress}";
        $attempts = Cache::get($attemptKey, 0);
        
        // If using identifier (like username), track per identifier
        if ($identifier) {
            $identifierKey = "login_attempts:{$identifier}:{$ipAddress}";
            $identifierAttempts = Cache::get($identifierKey, 0);
            
            if ($identifierAttempts >= $this->maxAttempts) {
                $this->blockIP($ipAddress, "Multiple failed login attempts for {$identifier}");
                
                $threats[] = [
                    'type' => 'brute_force',
                    'description' => "Multiple failed login attempts for {$identifier}",
                    'severity' => 4,
                    'blocked' => true,
                ];
                
                return $threats;
            }
        }
        
        // Check overall attempts for IP
        if ($attempts >= $this->maxAttempts * 2) { // Higher threshold for IP-wide blocking
            $this->blockIP($ipAddress, 'Excessive login attempts');
            
            $threats[] = [
                'type' => 'brute_force',
                'description' => 'Excessive login attempts from IP',
                'severity' => 4,
                'blocked' => true,
            ];
            
            return $threats;
        }
        
        // Check for rapid requests (more than 10 per second)
        $rapidKey = "rapid_requests:{$ipAddress}";
        $rapidCount = Cache::increment($rapidKey);
        Cache::put($rapidKey, $rapidCount, now()->addSeconds(1));
        
        if ($rapidCount > 10) {
            $threats[] = [
                'type' => 'brute_force',
                'description' => 'Rapid request pattern detected',
                'severity' => 3,
                'blocked' => false,
            ];
        }
        
        return $threats;
    }

    public function recordFailedAttempt(Request $request, string $identifier = null): void
    {
        $ipAddress = $request->ip();
        
        // Increment IP-based attempts
        $attemptKey = "login_attempts:{$ipAddress}";
        $attempts = Cache::increment($attemptKey);
        Cache::put($attemptKey, $attempts, now()->addMinutes(15));
        
        // Increment identifier-based attempts
        if ($identifier) {
            $identifierKey = "login_attempts:{$identifier}:{$ipAddress}";
            $identifierAttempts = Cache::increment($identifierKey);
            Cache::put($identifierKey, $identifierAttempts, now()->addMinutes(15));
            
            Log::warning('Failed login attempt', [
                'identifier' => $identifier,
                'ip' => $ipAddress,
                'attempts' => $identifierAttempts,
            ]);
        }
        
        // Check if we should block
        if ($attempts >= $this->maxAttempts * 3) {
            $this->blockIP($ipAddress, 'Excessive failed attempts');
        }
    }

    public function recordSuccessfulAttempt(Request $request, string $identifier = null): void
    {
        $ipAddress = $request->ip();
        
        // Reset counters
        Cache::forget("login_attempts:{$ipAddress}");
        
        if ($identifier) {
            Cache::forget("login_attempts:{$identifier}:{$ipAddress}");
        }
        
        Cache::forget("rapid_requests:{$ipAddress}");
    }

    private function blockIP(string $ipAddress, string $reason): void
    {
        $blocked = BlockedIP::where('ip_address', $ipAddress)->first();
        
        if ($blocked) {
            $blocked->incrementAttempts();
            $blocked->blocked_until = now()->addSeconds($this->blockDuration * $blocked->attempts);
            $blocked->reason = $reason;
            $blocked->save();
        } else {
            BlockedIP::create([
                'ip_address' => $ipAddress,
                'reason' => $reason,
                'blocked_until' => now()->addSeconds($this->blockDuration),
                'attempts' => 1,
            ]);
        }
        
        Log::critical('IP address blocked', [
            'ip' => $ipAddress,
            'reason' => $reason,
        ]);
    }

    public function isBlocked(string $ipAddress): bool
    {
        return BlockedIP::where('ip_address', $ipAddress)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    public function getAttemptsCount(string $ipAddress, string $identifier = null): int
    {
        if ($identifier) {
            $key = "login_attempts:{$identifier}:{$ipAddress}";
        } else {
            $key = "login_attempts:{$ipAddress}";
        }
        
        return Cache::get($key, 0);
    }

    public function clearAttempts(string $ipAddress, string $identifier = null): void
    {
        Cache::forget("login_attempts:{$ipAddress}");
        Cache::forget("rapid_requests:{$ipAddress}");
        
        if ($identifier) {
            Cache::forget("login_attempts:{$identifier}:{$ipAddress}");
        }
    }
}