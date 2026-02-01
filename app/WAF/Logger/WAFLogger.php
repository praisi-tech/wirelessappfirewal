<?php

namespace App\WAF\Logger;

use App\Models\WAFLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LaravelLog;

class WAFLogger
{
    public function logThreat(
        Request $request,
        string $threatType,
        string $description,
        int $severity = 1,
        bool $blocked = false,
        array $details = []
    ): void {
        try {
            WAFLog::create([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'request_data' => $this->sanitizeRequestData($request->all()),
                'threat_type' => $threatType,
                'description' => $description,
                'severity' => $severity,
                'blocked' => $blocked,
                'user_id' => Auth::id(),
            ]);
            
            // Also log to Laravel's log system based on severity
            $this->logToSystem($threatType, $description, $severity, $details);
            
        } catch (\Exception $e) {
            LaravelLog::error('Failed to log WAF threat', [
                'error' => $e->getMessage(),
                'threat_type' => $threatType,
                'description' => $description,
            ]);
        }
    }

    private function sanitizeRequestData(array $data): array
    {
        // Remove sensitive information
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'secret',
            'token',
            'api_key',
            'secret_key',
            'credit_card',
            'cvv',
            'ssn',
            'social_security',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }
        
        // Limit string lengths
        array_walk_recursive($data, function (&$value) {
            if (is_string($value) && strlen($value) > 1000) {
                $value = substr($value, 0, 1000) . '... [TRUNCATED]';
            }
        });
        
        return $data;
    }

    private function logToSystem(string $threatType, string $description, int $severity, array $details): void
    {
        $context = array_merge($details, [
            'threat_type' => $threatType,
            'severity' => $severity,
        ]);
        
        switch ($severity) {
            case 1: // Low
                LaravelLog::info("WAF: {$description}", $context);
                break;
            case 2: // Medium
                LaravelLog::warning("WAF: {$description}", $context);
                break;
            case 3: // High
                LaravelLog::error("WAF: {$description}", $context);
                break;
            case 4: // Critical
                LaravelLog::critical("WAF: {$description}", $context);
                break;
        }
    }

    public function logRequest(Request $request): void
    {
        // Log all requests (for auditing)
        if (config('waf.logging.enabled')) {
            WAFLog::create([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'request_data' => $this->sanitizeRequestData($request->all()),
                'threat_type' => null,
                'description' => 'Request processed',
                'severity' => 1,
                'blocked' => false,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function getStats(array $filters = []): array
    {
        $query = WAFLog::query();
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['severity'])) {
            $query->where('severity', '>=', $filters['severity']);
        }
        
        if (isset($filters['threat_type'])) {
            $query->where('threat_type', $filters['threat_type']);
        }
        
        $total = $query->count();
        $blocked = (clone $query)->where('blocked', true)->count();
        $byType = (clone $query)->whereNotNull('threat_type')
            ->groupBy('threat_type')
            ->selectRaw('threat_type, count(*) as count')
            ->pluck('count', 'threat_type')
            ->toArray();
        
        $bySeverity = (clone $query)->groupBy('severity')
            ->selectRaw('severity, count(*) as count')
            ->pluck('count', 'severity')
            ->toArray();
        
        $recentThreats = (clone $query)->whereNotNull('threat_type')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'total' => $total,
            'blocked' => $blocked,
            'by_type' => $byType,
            'by_severity' => $bySeverity,
            'recent_threats' => $recentThreats,
        ];
    }

    public function cleanupOldLogs(): int
    {
        $days = config('waf.logging.storage_days', 30);
        $cutoff = now()->subDays($days);
        
        return WAFLog::where('created_at', '<', $cutoff)->delete();
    }
}