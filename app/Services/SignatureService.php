<?php

namespace App\Services;

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

    public function verifyRequestSignature(Request $request, string $secret): bool
    {
        try {
            $signature = $request->header(config('crypto.headers.signature'));
            $timestamp = $request->header(config('crypto.headers.timestamp'));
            $nonce = $request->header(config('crypto.headers.nonce'));
            
            if (!$signature || !$timestamp || !$nonce) {
                return false;
            }
            
            // Check timestamp (prevent replay attacks)
            $currentTime = time();
            if (abs($currentTime - (int)$timestamp) > 300) { // 5 minutes window
                Log::warning('Request timestamp expired', [
                    'timestamp' => $timestamp,
                    'current' => $currentTime,
                ]);
                return false;
            }
            
            // Check nonce (prevent replay attacks)
            $nonceKey = 'nonce:' . $nonce;
            if (Cache::has($nonceKey)) {
                Log::warning('Nonce reuse detected', ['nonce' => $nonce]);
                return false;
            }
            
            Cache::put($nonceKey, true, now()->addMinutes(10));
            
            // Prepare data for signature verification
            $data = $request->all();
            $data['timestamp'] = $timestamp;
            $data['nonce'] = $nonce;
            $data['method'] = $request->method();
            $data['path'] = $request->path();
            
            // Remove signature from data if present
            unset($data['signature']);
            
            // Generate expected signature
            $expectedSignature = $this->crypto->generateSignature($data, $secret);
            
            // Verify signature
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Invalid signature', [
                    'expected' => $expectedSignature,
                    'received' => $signature,
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Signature verification failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return false;
        }
    }

    public function createSignedRequest(array $data, string $secret): array
    {
        $timestamp = time();
        $nonce = $this->crypto->generateNonce();
        
        $data['timestamp'] = $timestamp;
        $data['nonce'] = $nonce;
        
        $signature = $this->crypto->generateSignature($data, $secret);
        
        return [
            'data' => $data,
            'headers' => [
                config('crypto.headers.signature') => $signature,
                config('crypto.headers.timestamp') => $timestamp,
                config('crypto.headers.nonce') => $nonce,
            ],
        ];
    }

    public function encryptAndSign(array $data, string $key, string $secret): array
    {
        $encrypted = $this->crypto->encrypt(json_encode($data));
        $timestamp = time();
        $nonce = $this->crypto->generateNonce();
        
        $payload = [
            'ciphertext' => $encrypted['ciphertext'],
            'iv' => $encrypted['iv'],
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ];
        
        if (isset($encrypted['tag'])) {
            $payload['tag'] = $encrypted['tag'];
        }
        
        $signature = $this->crypto->generateSignature($payload, $secret);
        $payload['signature'] = $signature;
        
        return $payload;
    }

    public function decryptAndVerify(array $payload, string $key, string $secret): ?array
    {
        try {
            $signature = $payload['signature'];
            unset($payload['signature']);
            
            // Verify signature
            $expectedSignature = $this->crypto->generateSignature($payload, $secret);
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }
            
            // Check timestamp
            if (abs(time() - $payload['timestamp']) > 300) {
                return null;
            }
            
            // Decrypt data
            $encryptedData = [
                'ciphertext' => $payload['ciphertext'],
                'iv' => $payload['iv'],
            ];
            
            if (isset($payload['tag'])) {
                $encryptedData['tag'] = $payload['tag'];
            }
            
            $decrypted = $this->crypto->decrypt($encryptedData);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            Log::error('Decryption and verification failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}