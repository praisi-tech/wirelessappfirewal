<?php

namespace App\WAF\Detectors;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XSSDetector
{
    private array $patterns = [
        // Basic XSS patterns
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/javascript\s*:/i',
        '/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^>\s]+)/i',
        '/vbscript\s*:/i',
        '/expression\s*\([^)]*\)/i',
        '/url\s*\([^)]*\)/i',
        '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
        '/<frame\b[^>]*>(.*?)<\/frame>/is',
        '/<embed\b[^>]*>(.*?)<\/embed>/is',
        '/<object\b[^>]*>(.*?)<\/object>/is',
        '/<applet\b[^>]*>(.*?)<\/applet>/is',
        '/<meta\b[^>]*>/i',
        '/<link\b[^>]*>/i',
        '/<base\b[^>]*>/i',
        '/<form\b[^>]*>(.*?)<\/form>/is',
        '/<input\b[^>]*>/i',
        '/<button\b[^>]*>(.*?)<\/button>/is',
        '/<textarea\b[^>]*>(.*?)<\/textarea>/is',
        '/<select\b[^>]*>(.*?)<\/select>/is',
        '/<img\b[^>]*>/i',
        '/<svg\b[^>]*>(.*?)<\/svg>/is',
        '/<math\b[^>]*>(.*?)<\/math>/is',
        '/data\s*:/i',
        '/alert\s*\([^)]*\)/i',
        '/confirm\s*\([^)]*\)/i',
        '/prompt\s*\([^)]*\)/i',
        '/eval\s*\([^)]*\)/i',
        '/setTimeout\s*\([^)]*\)/i',
        '/setInterval\s*\([^)]*\)/i',
        '/Function\s*\([^)]*\)/i',
        '/<.*?\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^>]+)/i',
        '/&#x?[0-9a-f]+;/i', // HTML entities
        '/fromCharCode\s*\([^)]*\)/i',
        '/unescape\s*\([^)]*\)/i',
        '/escape\s*\([^)]*\)/i',
        '/document\./i',
        '/window\./i',
        '/location\./i',
        '/history\./i',
        '/navigator\./i',
        '/cookie\s*=/i',
        '/localStorage\./i',
        '/sessionStorage\./i',
        '/XMLHttpRequest/i',
        '/fetch\s*\([^)]*\)/i',
        '/\.src\s*=/i',
        '/\.href\s*=/i',
        '/\.action\s*=/i',
        '/\.innerHTML\s*=/i',
        '/\.outerHTML\s*=/i',
        '/\.write\s*\([^)]*\)/i',
        '/\.writeln\s*\([^)]*\)/i',
        '/<!\-\-.*?\-\->/s', // HTML comments
    ];

    public function __construct()
    {
        // Load additional patterns from config
        $customPatterns = config('waf.xss.patterns');
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
        
        // Check all input parameters
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                $detected = $this->scanString($value);
                if ($detected) {
                    $threats[] = [
                        'type' => 'xss',
                        'parameter' => $key,
                        'value' => substr($value, 0, 100),
                        'pattern' => $detected,
                        'severity' => 3, // High
                    ];
                }
            } elseif (is_array($value)) {
                $this->scanArray($value, $key, $threats);
            }
        }
        
        // Check headers
        foreach ($request->headers->all() as $name => $values) {
            $value = implode(', ', $values);
            $detected = $this->scanString($value);
            if ($detected) {
                $threats[] = [
                    'type' => 'xss',
                    'parameter' => "header:{$name}",
                    'value' => substr($value, 0, 100),
                    'pattern' => $detected,
                    'severity' => 3,
                ];
            }
        }
        
        return $threats;
    }

    private function scanArray(array $array, string $prefix, array &$threats): void
    {
        foreach ($array as $key => $value) {
            $fullKey = "{$prefix}[{$key}]";
            
            if (is_string($value)) {
                $detected = $this->scanString($value);
                if ($detected) {
                    $threats[] = [
                        'type' => 'xss',
                        'parameter' => $fullKey,
                        'value' => substr($value, 0, 100),
                        'pattern' => $detected,
                        'severity' => 3,
                    ];
                }
            } elseif (is_array($value)) {
                $this->scanArray($value, $fullKey, $threats);
            }
        }
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

    public function sanitize(string $input, bool $stripTags = true): string
    {
        if ($stripTags) {
            // Remove HTML tags but keep content
            $input = strip_tags($input);
        }
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Normalize newlines
        $input = preg_replace('/\r\n?/', "\n", $input);
        
        return trim($input);
    }

    public function hasScriptTags(string $input): bool
    {
        return preg_match('/<script\b[^>]*>/i', $input) ||
               preg_match('/<\/script>/i', $input);
    }

    public function hasEventHandlers(string $input): bool
    {
        return preg_match('/on\w+\s*=/i', $input);
    }

    public function hasJavaScriptProtocol(string $input): bool
    {
        return preg_match('/javascript\s*:/i', $input);
    }
}