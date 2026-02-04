<?php

namespace App\WAF\Detectors;

use Illuminate\Http\Request;

class XSSDetector
{
    // Add this method to skip certain parameters
    private function shouldSkipParameter($parameter, $value): bool
    {
        $parameter = strtolower($parameter);
        
        // Skip checking HTTP headers (they contain encoded data)
        if (str_starts_with($parameter, 'header:')) {
            $headerName = str_replace('header:', '', $parameter);
            $skipHeaders = ['cookie', 'laravel-session', 'xsrf-token', 'x-csrf-token', 'phpdebugbar'];
            
            foreach ($skipHeaders as $skip) {
                if (str_contains(strtolower($headerName), $skip)) {
                    return true;
                }
            }
        }
        
        // Skip base64-encoded session/JWT data
        if (is_string($value) && str_starts_with($value, 'eyJ') && strlen($value) > 50) {
            return true; // JWT-like data
        }
        
        // Skip long base64 strings (likely encoded data)
        if (is_string($value) && preg_match('/^[a-zA-Z0-9\/+=]+$/', $value) && strlen($value) > 100) {
            return true;
        }
        
        return false;
    }
    
    public function detect(Request $request): array
    {
        $threats = [];
        
        // Check all input parameters
        foreach ($request->all() as $param => $value) {
            if ($this->shouldSkipParameter($param, $value)) {
                continue; // Skip this parameter
            }
            
            if ($this->isXSS($value)) {
                $threats[] = [
                    'type' => 'xss',
                    'parameter' => $param,
                    'value' => substr((string)$value, 0, 100),
                    'description' => 'Potential XSS attack detected',
                    'severity' => 3,
                    'blocked' => false,
                ];
            }
        }
        
        // Check headers (but skip certain ones)
        foreach ($request->headers->all() as $header => $values) {
            $headerValue = implode(', ', $values);
            
            if ($this->shouldSkipParameter("header:$header", $headerValue)) {
                continue; // Skip this header
            }
            
            if ($this->isXSS($headerValue)) {
                $threats[] = [
                    'type' => 'xss',
                    'parameter' => "header:$header",
                    'value' => substr($headerValue, 0, 100),
                    'description' => 'Potential XSS in header',
                    'severity' => 2,
                    'blocked' => false,
                ];
            }
        }
        
        return $threats;
    }
    
    private function isXSS($input): bool
    {
        if (!is_string($input)) {
            return false;
        }
        
        // List of XSS patterns (more specific)
        $patterns = [
            // Script tags
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            
            // Event handlers (more specific)
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/onfocus\s*=/i',
            
            // Dangerous HTML tags
            '/<iframe[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i',
            '/<applet[^>]*>/i',
            
            // Data URI schemes
            '/data:/i',
            
            // Eval and expression
            '/eval\s*\(/i',
            '/expression\s*\(/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function sanitize(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
}