<?php

namespace App\WAF\Detectors;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SQLInjectionDetector
{
    private array $patterns = [
        // SQL Injection patterns
        '/(union\s+select)/i',
        '/(select.*from)/i',
        '/(insert\s+into)/i',
        '/(update\s+.+\s+set)/i',
        '/(delete\s+from)/i',
        '/(drop\s+table)/i',
        '/(truncate\s+table)/i',
        '/(--|\#)/', // SQL comments
        '/(\/\*.*\*\/)/', /* SQL comments */
        '/(waitfor\s+delay)/i',
        '/(sleep\s*\([^)]*\))/i',
        '/(benchmark\s*\([^)]*\))/i',
        '/(load_file\s*\([^)]*\))/i',
        '/(into\s+outfile\s*)/i',
        '/(into\s+dfile\s*)/i',
        '/(exec\s*\([^)]*\))/i',
        '/(xp_cmdshell)/i',
        '/(@@version)/i',
        '/(char\s*\([^)]*\))/i',
        '/(concat\s*\([^)]*\))/i',
        '/(group_concat\s*\([^)]*\))/i',
        '/(information_schema)/i',
        '/(table_schema)/i',
        '/(table_name)/i',
        '/(column_name)/i',
        '/(pg_sleep)/i',
        '/(dbms_pipe)/i',
        '/(utl_http)/i',
        '/(or\s*1\s*=\s*1)/i',
        '/(and\s*1\s*=\s*1)/i',
        '/(or\s*\'\'=\')/i',
        '/(and\s*\'\'=\')/i',
        '/(or\s*\d+\s*=\s*\d+)/i',
        '/(and\s*\d+\s*=\s*\d+)/i',
        '/(\'\s+or\s+\')/i',
        '/(\'\s+and\s+\')/i',
        '/(\|\|)/', // MySQL concatenation
        '/(&&)/', // MySQL AND
        '/(;\s*--)/', // End of query with comment
        '/(;\s*$)/', // Multiple queries
        '/(\b(\'|"|`)\s*[\+\-]\s*[\d\w]+\s*\b)/i',
    ];

    public function __construct()
    {
        // Load additional patterns from config
        $customPatterns = config('waf.sql_injection.patterns');
        if ($customPatterns && file_exists($customPatterns)) {
            $additional = json_decode(file_get_contents($customPatterns), true);
            if ($additional) {
                $this->patterns = array_merge($this->patterns, $additional);
            }
        }
    }

    public function detect(Request $request): array
    {
        $threats = [];
        
        // Check query parameters
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                $detected = $this->scanString($value);
                if ($detected) {
                    $threats[] = [
                        'type' => 'sql_injection',
                        'parameter' => $key,
                        'value' => substr($value, 0, 100), // Limit length
                        'pattern' => $detected,
                        'severity' => 4, // Critical
                    ];
                }
            }
        }
        
        // Check headers
        foreach ($request->headers->all() as $name => $values) {
            $value = implode(', ', $values);
            $detected = $this->scanString($value);
            if ($detected) {
                $threats[] = [
                    'type' => 'sql_injection',
                    'parameter' => "header:{$name}",
                    'value' => substr($value, 0, 100),
                    'pattern' => $detected,
                    'severity' => 3, // High
                ];
            }
        }
        
        return $threats;
    }

    private function scanString(string $input): ?string
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return $pattern;
            }
        }
        
        return null;
    }

    public function sanitize(string $input): string
    {
        // Remove SQL comments
        $input = preg_replace('/(--|\#).*$/m', '', $input);
        $input = preg_replace('/\/\*.*?\*\//s', '', $input);
        
        // Escape special characters
        $input = addslashes($input);
        
        // Remove multiple spaces
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }
}