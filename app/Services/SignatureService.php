<?php

namespace App\Services;

use App\Models\Nonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SignatureService
{
    private CryptoService $crypto;

    public function __construct(CryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Alias method agar cocok dengan panggilan di Middleware
     * (Menyelesaikan Milestone: Integrity Verified)
     */
    public function verify(Request $request, string $secret): bool
    {
        return $this->verifyRequestSignature($request, $secret);
    }

    /**
     * Helper untuk memanggil generateSignature dari CryptoService.
     * Digunakan oleh CryptoTest untuk validasi integritas.
     */
    public function generateSignature(array $data, string $secret): string
    {
        return $this->crypto->generateSignature($data, $secret);
    }

    /**
     * Validates an incoming request's authenticity and integrity.
     * MILESTONE: Automatic Protection + Replay Protection + Observability
     */
    public function verifyRequestSignature(Request $request, string $secret): bool
    {
        try {
            // Fetch headers using config-defined keys
            $sigHeader   = config('crypto.headers.signature', 'X-Signature');
            $timeHeader  = config('crypto.headers.timestamp', 'X-Timestamp');
            $nonceHeader = config('crypto.headers.nonce', 'X-Nonce');

            $signature = $request->header($sigHeader);
            $timestamp = $request->header($timeHeader);
            $nonce     = $request->header($nonceHeader);
            
            if (!$signature || !$timestamp || !$nonce) {
                Log::warning('WAF: Missing required signature headers', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'has_signature' => !empty($signature),
                    'has_timestamp' => !empty($timestamp),
                    'has_nonce' => !empty($nonce),
                ]);
                return false;
            }
            
            // 1. Check timestamp (Prevent Replay Attacks)
            // Ensures the request was generated within the last 5 minutes
            if (abs(time() - (int)$timestamp) > 300) {
                Log::warning('WAF: Request signature expired', [
                    'timestamp' => $timestamp,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'age_seconds' => abs(time() - (int)$timestamp),
                ]);
                return false;
            }
            
            // 2. Check nonce (MILESTONE: Replay Protection)
            // Nonces should only be used once within the timestamp window
            // Check both cache and database for persistence
            $nonceKey = 'crypto_nonce:' . $nonce;
            
            // Check cache first (faster)
            if (Cache::has($nonceKey)) {
                Log::warning('WAF: Nonce reuse detected (Replay Attack - Cache)', [
                    'nonce' => substr($nonce, 0, 8) . '...',
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);
                return false;
            }
            
            // Check database for persistence across cache clears
            $existingNonce = Nonce::where('nonce', $nonce)
                                  ->valid()
                                  ->first();
            
            if ($existingNonce) {
                Log::warning('WAF: Nonce reuse detected (Replay Attack - Database)', [
                    'nonce' => substr($nonce, 0, 8) . '...',
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'previous_usage' => $existingNonce->created_at,
                ]);
                return false;
            }
            
            // 3. Reconstruct data for verification
            // We must mirror exactly how the signature was built
            $data = $request->all();
            $data['timestamp'] = $timestamp;
            $data['nonce']     = $nonce;
            $data['method']    = $request->method();
            $data['path']      = $request->path();
            
            // Ensure any existing signature in body isn't part of the signed payload
            unset($data['signature']);
            
            $expectedSignature = $this->crypto->generateSignature($data, $secret);
            
            // Constant-time comparison to prevent timing attacks
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('WAF: Invalid request signature received', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'expected' => substr($expectedSignature, 0, 8) . '...',
                    'received' => substr($signature, 0, 8) . '...',
                ]);
                return false;
            }

            // If all checks pass, commit the nonce to both cache and database to prevent reuse
            // Cache for fast checking (10 minutes)
            Cache::put($nonceKey, true, now()->addMinutes(10));
            
            // Database for persistence across cache clears
            Nonce::create([
                'nonce' => $nonce,
                'ip_address' => $request->ip(),
                'expires_at' => now()->addMinutes(10),
            ]);
            
            Log::info('WAF: Request signature verified successfully', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('WAF: Signature verification failed with exception', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Creates a signed request data package and headers.
     */
    public function createSignedRequest(array $data, string $secret): array
    {
        $timestamp = time();
        $nonce     = $this->crypto->generateNonce();
        
        $payload = $data;
        $payload['timestamp'] = $timestamp;
        $payload['nonce']     = $nonce;
        $payload['method']    = request()->method();
        $payload['path']      = request()->path();
        
        $signature = $this->crypto->generateSignature($payload, $secret);
        
        return [
            'data' => $data,
            'headers' => [
                config('crypto.headers.signature', 'X-Signature') => $signature,
                config('crypto.headers.timestamp', 'X-Timestamp') => $timestamp,
                config('crypto.headers.nonce', 'X-Nonce')         => $nonce,
            ],
        ];
    }

    /**
     * Encrypt-then-Sign (Milestone: Persistence & Integrity)
     */
    public function encryptAndSign(array $data, string $key, string $secret): array
    {
        // 1. Encrypt the raw data
        $encrypted = $this->crypto->encrypt(json_encode($data), $key);
        
        $timestamp = time();
        $nonce     = $this->crypto->generateNonce();
        
        // 2. Build the payload of ciphertext and metadata
        $payload = [
            'ciphertext' => $encrypted['ciphertext'],
            'iv'         => $encrypted['iv'],
            'timestamp'  => $timestamp,
            'nonce'      => $nonce,
        ];
        
        if (isset($encrypted['tag'])) {
            $payload['tag'] = $encrypted['tag'];
        }
        
        // 3. Sign the ENTIRE encrypted package
        $payload['signature'] = $this->crypto->generateSignature($payload, $secret);
        
        return $payload;
    }

    /**
     * Verify-then-Decrypt
     */
    public function decryptAndVerify(array $payload, string $key, string $secret): ?array
    {
        try {
            if (!isset($payload['signature'], $payload['timestamp'])) {
                return null;
            }

            $signature = $payload['signature'];
            $verifyData = $payload;
            unset($verifyData['signature']);
            
            // 1. Verify Signature first
            $expectedSignature = $this->crypto->generateSignature($verifyData, $secret);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('WAF: Payload signature mismatch');
                return null;
            }
            
            // 2. Verify Timestamp
            if (abs(time() - (int)$payload['timestamp']) > 300) {
                Log::warning('WAF: Payload expired');
                return null;
            }
            
            // 3. Decrypt the ciphertext
            $encryptedData = [
                'ciphertext' => $payload['ciphertext'],
                'iv'         => $payload['iv'],
                'tag'        => $payload['tag'] ?? null,
            ];
            
            $decrypted = $this->crypto->decrypt($encryptedData, $key);
            
            return json_decode($decrypted, true);

        } catch (\Exception $e) {
            Log::error('WAF: Decryption and verification failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}